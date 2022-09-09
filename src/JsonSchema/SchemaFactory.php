<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\JsonSchema;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface
{
    use ResourceClassInfoTrait;

    private $typeFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $nameConverter;
    private $distinctFormats = [];

    public function __construct(TypeFactoryInterface $typeFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, NameConverterInterface $nameConverter = null, ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->typeFactory = $typeFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->nameConverter = $nameConverter;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * When added to the list, the given format will lead to the creation of a new definition.
     *
     * @internal
     */
    public function addDistinctFormat(string $format): void
    {
        $this->distinctFormats[$format] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $schema ? clone $schema : new Schema();

        if (null === $metadata = $this->getMetadata($className, $type, $operation, $serializerContext)) {
            return $schema;
        }

        [$operation, $serializerContext, $validationGroups, $inputOrOutputClass] = $metadata;

        $version = $schema->getVersion();
        $definitionName = $this->buildDefinitionName($className, $format, $inputOrOutputClass, $operation, $serializerContext);

        $method = $operation instanceof HttpOperation ? $operation->getMethod() : 'GET';
        if (!$operation) {
            $method = Schema::TYPE_INPUT === $type ? 'POST' : 'GET';
        }

        if (Schema::TYPE_OUTPUT !== $type && !\in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            return $schema;
        }

        if (!isset($schema['$ref']) && !isset($schema['type'])) {
            $ref = Schema::VERSION_OPENAPI === $version ? '#/components/schemas/'.$definitionName : '#/definitions/'.$definitionName;
            if ($forceCollection || ('POST' !== $method && $operation instanceof CollectionOperationInterface)) {
                $schema['type'] = 'array';
                $schema['items'] = ['$ref' => $ref];
            } else {
                $schema['$ref'] = $ref;
            }
        }

        $definitions = $schema->getDefinitions();
        if (isset($definitions[$definitionName])) {
            // Already computed
            return $schema;
        }

        /** @var \ArrayObject<string, mixed> $definition */
        $definition = new \ArrayObject(['type' => 'object']);
        $definitions[$definitionName] = $definition;
        $definition['description'] = $operation ? ($operation->getDescription() ?? '') : '';

        // additionalProperties are allowed by default, so it does not need to be set explicitly, unless allow_extra_attributes is false
        // See https://json-schema.org/understanding-json-schema/reference/object.html#properties
        if (false === ($serializerContext[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] ?? true)) {
            $definition['additionalProperties'] = false;
        }

        // see https://github.com/json-schema-org/json-schema-spec/pull/737
        if (Schema::VERSION_SWAGGER !== $version && $operation && $operation->getDeprecationReason()) {
            $definition['deprecated'] = true;
        }

        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        if ($operation instanceof HttpOperation && ($operation->getTypes()[0] ?? null)) {
            $definition['externalDocs'] = ['url' => $operation->getTypes()[0]];
        }

        $options = $this->getFactoryOptions($serializerContext, $validationGroups, $operation instanceof HttpOperation ? $operation : null);
        foreach ($this->propertyNameCollectionFactory->create($inputOrOutputClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($inputOrOutputClass, $propertyName, $options);
            if (!$propertyMetadata->isReadable() && !$propertyMetadata->isWritable()) {
                continue;
            }

            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $inputOrOutputClass, $format, $serializerContext) : $propertyName;
            if ($propertyMetadata->isRequired()) {
                $definition['required'][] = $normalizedPropertyName;
            }

            $this->buildPropertySchema($schema, $definitionName, $normalizedPropertyName, $propertyMetadata, $serializerContext, $format);
        }

        return $schema;
    }

    private function buildPropertySchema(Schema $schema, string $definitionName, string $normalizedPropertyName, ApiProperty $propertyMetadata, array $serializerContext, string $format): void
    {
        $version = $schema->getVersion();
        $swagger = Schema::VERSION_SWAGGER === $version;
        if (Schema::VERSION_SWAGGER === $version || Schema::VERSION_OPENAPI === $version) {
            $additionalPropertySchema = $propertyMetadata->getOpenapiContext();
        } else {
            $additionalPropertySchema = $propertyMetadata->getJsonSchemaContext();
        }

        $propertySchema = array_merge(
            $propertyMetadata->getSchema() ?? [],
            $additionalPropertySchema ?? []
        );

        if (false === $propertyMetadata->isWritable() && !$propertyMetadata->isInitializable()) {
            $propertySchema['readOnly'] = true;
        }
        if (!$swagger && false === $propertyMetadata->isReadable()) {
            $propertySchema['writeOnly'] = true;
        }
        if (null !== $description = $propertyMetadata->getDescription()) {
            $propertySchema['description'] = $description;
        }

        $deprecationReason = $propertyMetadata->getDeprecationReason();

        // see https://github.com/json-schema-org/json-schema-spec/pull/737
        if (!$swagger && null !== $deprecationReason) {
            $propertySchema['deprecated'] = true;
        }
        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        $iri = $propertyMetadata->getTypes()[0] ?? null;
        if (null !== $iri) {
            $propertySchema['externalDocs'] = ['url' => $iri];
        }

        if (!isset($propertySchema['default']) && !empty($default = $propertyMetadata->getDefault())) {
            $propertySchema['default'] = $default;
        }

        if (!isset($propertySchema['example']) && !empty($example = $propertyMetadata->getExample())) {
            $propertySchema['example'] = $example;
        }

        if (!isset($propertySchema['example']) && isset($propertySchema['default'])) {
            $propertySchema['example'] = $propertySchema['default'];
        }

        $valueSchema = [];
        // TODO: 3.0 support multiple types
        $type = $propertyMetadata->getBuiltinTypes()[0] ?? null;
        if (null !== $type) {
            if ($isCollection = $type->isCollection()) {
                $keyType = method_exists(Type::class, 'getCollectionKeyTypes') ? ($type->getCollectionKeyTypes()[0] ?? null) : $type->getCollectionKeyType();
                $valueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType();
            } else {
                $keyType = null;
                $valueType = $type;
            }

            if (null === $valueType) {
                $builtinType = 'string';
                $className = null;
            } else {
                $builtinType = $valueType->getBuiltinType();
                $className = $valueType->getClassName();
            }

            $valueSchema = $this->typeFactory->getType(new Type($builtinType, $type->isNullable(), $className, $isCollection, $keyType, $valueType), $format, $propertyMetadata->isReadableLink(), $serializerContext, $schema);
        }

        if (\array_key_exists('type', $propertySchema) && \array_key_exists('$ref', $valueSchema)) {
            $propertySchema = new \ArrayObject($propertySchema);
        } else {
            $propertySchema = new \ArrayObject($propertySchema + $valueSchema);
        }
        $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = $propertySchema;
    }

    private function buildDefinitionName(string $className, string $format = 'json', ?string $inputOrOutputClass = null, Operation $operation = null, ?array $serializerContext = null): string
    {
        if ($operation) {
            $prefix = $operation->getShortName();
        }

        if (!isset($prefix)) {
            $prefix = (new \ReflectionClass($className))->getShortName();
        }

        if (null !== $inputOrOutputClass && $className !== $inputOrOutputClass) {
            $parts = explode('\\', $inputOrOutputClass);
            $shortName = end($parts);
            $prefix .= '.'.$shortName;
        }

        if (isset($this->distinctFormats[$format])) {
            // JSON is the default, and so isn't included in the definition name
            $prefix .= '.'.$format;
        }

        $definitionName = $serializerContext[OpenApiFactory::OPENAPI_DEFINITION_NAME] ?? null;
        if ($definitionName) {
            $name = sprintf('%s-%s', $prefix, $definitionName);
        } else {
            $groups = (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []);
            $name = $groups ? sprintf('%s-%s', $prefix, implode('_', $groups)) : $prefix;
        }

        return $this->encodeDefinitionName($name);
    }

    private function encodeDefinitionName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9.\-_]/', '.', $name);
    }

    private function getMetadata(string $className, string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?array $serializerContext = null): ?array
    {
        if (!$this->isResourceClass($className)) {
            return [
                null,
                $serializerContext ?? [],
                [],
                $className,
            ];
        }

        // The best here is to use an Operation when calling `buildSchema`, we try to do a smart guess otherwise
        if (!$operation || !$operation->getClass()) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($className);

            if ($operation && $operation->getName()) {
                $operation = $resourceMetadataCollection->getOperation($operation->getName());
            } else {
                // Guess the operation and use the first one that matches criterias
                foreach ($resourceMetadataCollection as $resourceMetadata) {
                    foreach ($resourceMetadata->getOperations() ?? [] as $op) {
                        if ($operation instanceof CollectionOperationInterface && $op instanceof CollectionOperationInterface) {
                            $operation = $op;
                            break 2;
                        }

                        if (Schema::TYPE_INPUT === $type && \in_array($op->getMethod(), ['POST', 'PATCH', 'PUT'], true)) {
                            $operation = $op;
                            break 2;
                        }

                        if (!$operation) {
                            $operation = new HttpOperation();
                        }
                    }
                }
            }
        }

        $attribute = Schema::TYPE_OUTPUT === $type ? 'output' : 'input';
        $inputOrOutput = ['class' => $className];

        if ($operation) {
            $inputOrOutput = Schema::TYPE_OUTPUT === $type ? ($operation->getOutput() ?? $inputOrOutput) : ($operation->getInput() ?? $inputOrOutput);
        }

        if (null === ($inputOrOutput['class'] ?? $inputOrOutput->class ?? null)) {
            // input or output disabled
            return null;
        }

        if (!$operation) {
            return [$operation, $serializerContext ?? [], [], $inputOrOutput['class'] ?? $inputOrOutput->class];
        }

        return [
            $operation,
            $serializerContext ?? $this->getSerializerContext($operation, $type),
            $this->getValidationGroups($operation),
            $inputOrOutput['class'] ?? $inputOrOutput->class,
        ];
    }

    private function getSerializerContext(?Operation $operation, string $type = Schema::TYPE_OUTPUT): array
    {
        return Schema::TYPE_OUTPUT === $type ? ($operation->getNormalizationContext() ?? []) : ($operation->getDenormalizationContext() ?? []);
    }

    private function getValidationGroups(Operation $operation): array
    {
        $groups = $operation->getValidationContext()['groups'] ?? [];

        return \is_array($groups) ? $groups : [$groups];
    }

    /**
     * Gets the options for the property name collection / property metadata factories.
     */
    private function getFactoryOptions(array $serializerContext, array $validationGroups, ?HttpOperation $operation = null): array
    {
        $options = [
            /* @see https://github.com/symfony/symfony/blob/v5.1.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php */
            'enable_getter_setter_extraction' => true,
        ];

        if (isset($serializerContext[AbstractNormalizer::GROUPS])) {
            /* @see https://github.com/symfony/symfony/blob/v4.2.6/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php */
            $options['serializer_groups'] = (array) $serializerContext[AbstractNormalizer::GROUPS];
        }

        if ($operation && ($normalizationGroups = $operation->getNormalizationContext()['groups'] ?? null)) {
            $options['normalization_groups'] = $normalizationGroups;
        }

        if ($operation && ($denormalizationGroups = $operation->getDenormalizationContext()['groups'] ?? null)) {
            $options['denormalization_groups'] = $denormalizationGroups;
        }

        if ($validationGroups) {
            $options['validation_groups'] = $validationGroups;
        }

        return $options;
    }
}
