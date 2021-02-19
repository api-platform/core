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

namespace ApiPlatform\Core\JsonSchema;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * {@inheritdoc}
 *
 * @experimental
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

    public function __construct(TypeFactoryInterface $typeFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, NameConverterInterface $nameConverter = null, ResourceClassResolverInterface $resourceClassResolver = null)
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
    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $schema ?? new Schema();
        if (null === $metadata = $this->getMetadata($className, $type, $operationType, $operationName, $serializerContext)) {
            return $schema;
        }
        [$resourceMetadata, $serializerContext, $validationGroups, $inputOrOutputClass] = $metadata;

        if (null === $resourceMetadata && (null !== $operationType || null !== $operationName)) {
            throw new \LogicException('The $operationType and $operationName arguments must be null for non-resource class.');
        }

        $version = $schema->getVersion();
        $definitionName = $this->buildDefinitionName($className, $format, $type, $operationType, $operationName, $serializerContext);

        if (null === $operationType || null === $operationName) {
            $method = Schema::TYPE_INPUT === $type ? 'POST' : 'GET';
        } else {
            $method = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'method');
        }

        if (Schema::TYPE_OUTPUT !== $type && !\in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            return $schema;
        }

        if (!isset($schema['$ref']) && !isset($schema['type'])) {
            $ref = Schema::VERSION_OPENAPI === $version ? '#/components/schemas/'.$definitionName : '#/definitions/'.$definitionName;

            $method = null !== $operationType && null !== $operationName ? $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'method', 'GET') : 'GET';
            if ($forceCollection || (OperationType::COLLECTION === $operationType && 'POST' !== $method)) {
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
        if (null !== $resourceMetadata && null !== $description = $resourceMetadata->getDescription()) {
            $definition['description'] = $description;
        }

        // additionalProperties are allowed by default, so it does not need to be set explicitly, unless allow_extra_attributes is false
        // See https://json-schema.org/understanding-json-schema/reference/object.html#properties
        if (false === ($serializerContext[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES] ?? true)) {
            $definition['additionalProperties'] = false;
        }

        // see https://github.com/json-schema-org/json-schema-spec/pull/737
        if (
            Schema::VERSION_SWAGGER !== $version &&
            null !== $resourceMetadata &&
            (
                (null !== $operationType && null !== $operationName && null !== $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'deprecation_reason', null, true)) ||
                null !== $resourceMetadata->getAttribute('deprecation_reason', null)
            )
        ) {
            $definition['deprecated'] = true;
        }
        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        if (null !== $resourceMetadata && null !== $iri = $resourceMetadata->getIri()) {
            $definition['externalDocs'] = ['url' => $iri];
        }

        $options = $this->getFactoryOptions($serializerContext, $validationGroups, $operationType, $operationName);
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

    private function buildPropertySchema(Schema $schema, string $definitionName, string $normalizedPropertyName, PropertyMetadata $propertyMetadata, array $serializerContext, string $format): void
    {
        $version = $schema->getVersion();
        $swagger = false;
        $propertySchema = $propertyMetadata->getSchema() ?? [];

        switch ($version) {
            case Schema::VERSION_SWAGGER:
                $swagger = true;
                $basePropertySchemaAttribute = 'swagger_context';
                break;
            case Schema::VERSION_OPENAPI:
                $basePropertySchemaAttribute = 'openapi_context';
                break;
            default:
                $basePropertySchemaAttribute = 'json_schema_context';
        }

        $propertySchema = array_merge(
            $propertySchema,
            $propertyMetadata->getAttributes()[$basePropertySchemaAttribute] ?? []
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
        // see https://github.com/json-schema-org/json-schema-spec/pull/737
        if (!$swagger && null !== $propertyMetadata->getAttribute('deprecation_reason')) {
            $propertySchema['deprecated'] = true;
        }
        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        if (null !== $iri = $propertyMetadata->getIri()) {
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
        if (null !== $type = $propertyMetadata->getType()) {
            if ($isCollection = $type->isCollection()) {
                $valueType = method_exists(Type::class, 'getCollectionValueTypes') ? ($type->getCollectionValueTypes()[0] ?? null) : $type->getCollectionValueType();
            } else {
                $valueType = $type;
            }

            if (null === $valueType) {
                $builtinType = 'string';
                $className = null;
            } else {
                $builtinType = $valueType->getBuiltinType();
                $className = $valueType->getClassName();
            }

            $valueSchema = $this->typeFactory->getType(new Type($builtinType, $type->isNullable(), $className, $isCollection), $format, $propertyMetadata->isReadableLink(), $serializerContext, $schema);
        }

        if (\array_key_exists('type', $propertySchema) && \array_key_exists('$ref', $valueSchema)) {
            $propertySchema = new \ArrayObject($propertySchema);
        } else {
            $propertySchema = new \ArrayObject($propertySchema + $valueSchema);
        }
        $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = $propertySchema;
    }

    private function buildDefinitionName(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?array $serializerContext = null): string
    {
        [$resourceMetadata, $serializerContext,, $inputOrOutputClass] = $this->getMetadata($className, $type, $operationType, $operationName, $serializerContext);

        $prefix = $resourceMetadata ? $resourceMetadata->getShortName() : (new \ReflectionClass($className))->getShortName();
        if (null !== $inputOrOutputClass && $className !== $inputOrOutputClass) {
            $parts = explode('\\', $inputOrOutputClass);
            $shortName = end($parts);
            $prefix .= '.'.$shortName;
        }

        if (isset($this->distinctFormats[$format])) {
            // JSON is the default, and so isn't included in the definition name
            $prefix .= '.'.$format;
        }

        $definitionName = $serializerContext[OpenApiFactory::OPENAPI_DEFINITION_NAME] ?? $serializerContext[DocumentationNormalizer::SWAGGER_DEFINITION_NAME] ?? null;
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

    private function getMetadata(string $className, string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?array $serializerContext = null): ?array
    {
        if (!$this->isResourceClass($className)) {
            return [
                null,
                $serializerContext ?? [],
                [],
                $className,
            ];
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($className);
        $attribute = Schema::TYPE_OUTPUT === $type ? 'output' : 'input';
        if (null === $operationType || null === $operationName) {
            $inputOrOutput = $resourceMetadata->getAttribute($attribute, ['class' => $className]);
        } else {
            $inputOrOutput = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, $attribute, ['class' => $className], true);
        }

        if (null === ($inputOrOutput['class'] ?? null)) {
            // input or output disabled
            return null;
        }

        return [
            $resourceMetadata,
            $serializerContext ?? $this->getSerializerContext($resourceMetadata, $type, $operationType, $operationName),
            $this->getValidationGroups($resourceMetadata, $operationType, $operationName),
            $inputOrOutput['class'],
        ];
    }

    private function getSerializerContext(ResourceMetadata $resourceMetadata, string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null): array
    {
        $attribute = Schema::TYPE_OUTPUT === $type ? 'normalization_context' : 'denormalization_context';

        if (null === $operationType || null === $operationName) {
            return $resourceMetadata->getAttribute($attribute, []);
        }

        return $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, $attribute, [], true);
    }

    private function getValidationGroups(ResourceMetadata $resourceMetadata, ?string $operationType, ?string $operationName): array
    {
        $attribute = 'validation_groups';

        if (null === $operationType || null === $operationName) {
            return \is_array($validationGroups = $resourceMetadata->getAttribute($attribute, [])) ? $validationGroups : [];
        }

        return \is_array($validationGroups = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, $attribute, [], true)) ? $validationGroups : [];
    }

    /**
     * Gets the options for the property name collection / property metadata factories.
     */
    private function getFactoryOptions(array $serializerContext, array $validationGroups, ?string $operationType, ?string $operationName): array
    {
        $options = [
            /* @see https://github.com/symfony/symfony/blob/v5.1.0/src/Symfony/Component/PropertyInfo/Extractor/ReflectionExtractor.php */
            'enable_getter_setter_extraction' => true,
        ];

        if (isset($serializerContext[AbstractNormalizer::GROUPS])) {
            /* @see https://github.com/symfony/symfony/blob/v4.2.6/src/Symfony/Component/PropertyInfo/Extractor/SerializerExtractor.php */
            $options['serializer_groups'] = (array) $serializerContext[AbstractNormalizer::GROUPS];
        }

        if (null !== $operationType && null !== $operationName) {
            switch ($operationType) {
                case OperationType::COLLECTION:
                    $options['collection_operation_name'] = $operationName;
                    break;
                case OperationType::ITEM:
                    $options['item_operation_name'] = $operationName;
                    break;
                default:
                    break;
            }
        }

        if ($validationGroups) {
            $options['validation_groups'] = $validationGroups;
        }

        return $options;
    }
}
