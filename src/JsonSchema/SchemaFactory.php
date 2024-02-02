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

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    use ResourceClassInfoTrait;
    private array $distinctFormats = [];
    private ?TypeFactoryInterface $typeFactory = null;
    private ?SchemaFactoryInterface $schemaFactory = null;
    // Edge case where the related resource is not readable (for example: NotExposed) but we have groups to read the whole related object
    public const FORCE_SUBSCHEMA = '_api_subschema_force_readable_link';
    public const OPENAPI_DEFINITION_NAME = 'openapi_definition_name';

    public function __construct(?TypeFactoryInterface $typeFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ?NameConverterInterface $nameConverter = null, ?ResourceClassResolverInterface $resourceClassResolver = null)
    {
        if ($typeFactory) {
            $this->typeFactory = $typeFactory;
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
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

        // In case of FORCE_SUBSCHEMA an object can be writable through another class eventhough it has no POST operation
        if (!($serializerContext[self::FORCE_SUBSCHEMA] ?? false) && Schema::TYPE_OUTPUT !== $type && !\in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
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
        } else {
            $definition['deprecated'] = false;
        }

        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        if ($operation instanceof HttpOperation && ($operation->getTypes()[0] ?? null)) {
            $definition['externalDocs'] = ['url' => $operation->getTypes()[0]];
        }

        $options = ['schema_type' => $type] + $this->getFactoryOptions($serializerContext, $validationGroups, $operation instanceof HttpOperation ? $operation : null);
        foreach ($this->propertyNameCollectionFactory->create($inputOrOutputClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($inputOrOutputClass, $propertyName, $options);
            if (!$propertyMetadata->isReadable() && !$propertyMetadata->isWritable()) {
                continue;
            }

            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $inputOrOutputClass, $format, $serializerContext) : $propertyName;
            if ($propertyMetadata->isRequired()) {
                $definition['required'][] = $normalizedPropertyName;
            }

            $this->buildPropertySchema($schema, $definitionName, $normalizedPropertyName, $propertyMetadata, $serializerContext, $format, $type);
        }

        return $schema;
    }

    private function buildPropertySchema(Schema $schema, string $definitionName, string $normalizedPropertyName, ApiProperty $propertyMetadata, array $serializerContext, string $format, string $parentType): void
    {
        $version = $schema->getVersion();
        if (Schema::VERSION_SWAGGER === $version || Schema::VERSION_OPENAPI === $version) {
            $additionalPropertySchema = $propertyMetadata->getOpenapiContext();
        } else {
            $additionalPropertySchema = $propertyMetadata->getJsonSchemaContext();
        }

        $propertySchema = array_merge(
            $propertyMetadata->getSchema() ?? [],
            $additionalPropertySchema ?? []
        );

        $extraProperties = $propertyMetadata->getExtraProperties() ?? [];
        // see AttributePropertyMetadataFactory
        if (true === ($extraProperties[SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED] ?? false)) {
            // schema seems to have been declared by the user: do not override nor complete user value
            $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);

            return;
        }

        $types = $propertyMetadata->getBuiltinTypes() ?? [];

        // never override the following keys if at least one is already set
        // or if property has no type(s) defined
        // or if property schema is already fully defined (type=string + format || enum)
        $propertySchemaType = $propertySchema['type'] ?? false;

        $isUnknown = Schema::UNKNOWN_TYPE === $propertySchemaType
            || ('array' === $propertySchemaType && Schema::UNKNOWN_TYPE === ($propertySchema['items']['type'] ?? null));

        if (
            !$isUnknown && (
                [] === $types
                || ($propertySchema['$ref'] ?? $propertySchema['anyOf'] ?? $propertySchema['allOf'] ?? $propertySchema['oneOf'] ?? false)
                || (\is_array($propertySchemaType) ? \array_key_exists('string', $propertySchemaType) : 'string' !== $propertySchemaType)
                || ($propertySchema['format'] ?? $propertySchema['enum'] ?? false)
            )
        ) {
            $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);

            return;
        }

        // property schema is created in SchemaPropertyMetadataFactory, but it cannot build resource reference ($ref)
        // complete property schema with resource reference ($ref) only if it's related to an object
        $version = $schema->getVersion();
        $subSchema = new Schema($version);
        $subSchema->setDefinitions($schema->getDefinitions()); // Populate definitions of the main schema

        foreach ($types as $type) {
            // TODO: in 3.3 add trigger_deprecation() as type factories are not used anymore, we moved this logic to SchemaPropertyMetadataFactory so that it gets cached
            if ($typeFromFactory = $this->typeFactory?->getType($type, 'jsonschema', $propertyMetadata->isReadableLink(), $serializerContext)) {
                $propertySchema = $typeFromFactory;
                break;
            }

            $isCollection = $type->isCollection();
            if ($isCollection) {
                $valueType = $type->getCollectionValueTypes()[0] ?? null;
            } else {
                $valueType = $type;
            }

            $className = $valueType?->getClassName();
            if (null === $className) {
                continue;
            }

            $subSchemaFactory = $this->schemaFactory ?: $this;
            $subSchema = $subSchemaFactory->buildSchema($className, $format, $parentType, null, $subSchema, $serializerContext + [self::FORCE_SUBSCHEMA => true], false);
            if (!isset($subSchema['$ref'])) {
                continue;
            }

            if ($isCollection) {
                $propertySchema['items']['$ref'] = $subSchema['$ref'];
                unset($propertySchema['items']['type']);
                break;
            }

            if ($type->isNullable()) {
                $propertySchema['anyOf'] = [['$ref' => $subSchema['$ref']], ['type' => 'null']];
            } else {
                $propertySchema['$ref'] = $subSchema['$ref'];
            }

            unset($propertySchema['type']);
            break;
        }

        $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);
    }

    private function buildDefinitionName(string $className, string $format = 'json', ?string $inputOrOutputClass = null, ?Operation $operation = null, ?array $serializerContext = null): string
    {
        if ($operation) {
            $prefix = $operation->getShortName();
        }

        if (!isset($prefix)) {
            $prefix = (new \ReflectionClass($className))->getShortName();
        }

        if (null !== $inputOrOutputClass && $className !== $inputOrOutputClass) {
            $shortName = $this->getShortClassName($inputOrOutputClass);
            $prefix .= '.'.$shortName;
        }

        if (isset($this->distinctFormats[$format])) {
            // JSON is the default, and so isn't included in the definition name
            $prefix .= '.'.$format;
        }

        $definitionName = $serializerContext[self::OPENAPI_DEFINITION_NAME] ?? null;
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

        $forceSubschema = $serializerContext[self::FORCE_SUBSCHEMA] ?? false;
        if (null === $operation) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($className);
            try {
                $operation = $resourceMetadataCollection->getOperation();
            } catch (OperationNotFoundException $e) {
                $operation = new HttpOperation();
            }
            if ($operation->getShortName() === $this->getShortClassName($className) && $forceSubschema) {
                $operation = new HttpOperation();
            }

            $operation = $this->findOperationForType($resourceMetadataCollection, $type, $operation);
        } else {
            // The best here is to use an Operation when calling `buildSchema`, we try to do a smart guess otherwise
            if (!$operation->getClass()) {
                $resourceMetadataCollection = $this->resourceMetadataFactory->create($className);

                if ($operation->getName()) {
                    $operation = $resourceMetadataCollection->getOperation($operation->getName());
                } else {
                    $operation = $this->findOperationForType($resourceMetadataCollection, $type, $operation);
                }
            }
        }

        $inputOrOutput = ['class' => $className];
        $inputOrOutput = Schema::TYPE_OUTPUT === $type ? ($operation->getOutput() ?? $inputOrOutput) : ($operation->getInput() ?? $inputOrOutput);
        $outputClass = $forceSubschema ? ($inputOrOutput['class'] ?? $inputOrOutput->class ?? $operation->getClass()) : ($inputOrOutput['class'] ?? $inputOrOutput->class ?? null);

        if (null === $outputClass) {
            // input or output disabled
            return null;
        }

        return [
            $operation,
            $serializerContext ?? $this->getSerializerContext($operation, $type),
            $this->getValidationGroups($operation),
            $outputClass,
        ];
    }

    private function findOperationForType(ResourceMetadataCollection $resourceMetadataCollection, string $type, Operation $operation): Operation
    {
        // Find the operation and use the first one that matches criterias
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
            }
        }

        return $operation;
    }

    private function getSerializerContext(Operation $operation, string $type = Schema::TYPE_OUTPUT): array
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

    private function getShortClassName(string $fullyQualifiedName): string
    {
        $parts = explode('\\', $fullyQualifiedName);

        return end($parts);
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        $this->schemaFactory = $schemaFactory;
    }
}
