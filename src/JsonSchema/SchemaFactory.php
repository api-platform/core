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
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeIdentifier;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    use ResourceMetadataTrait;
    use SchemaUriPrefixTrait;

    private ?SchemaFactoryInterface $schemaFactory = null;
    // Edge case where the related resource is not readable (for example: NotExposed) but we have groups to read the whole related object
    public const OPENAPI_DEFINITION_NAME = 'openapi_definition_name';

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ?NameConverterInterface $nameConverter = null, ?ResourceClassResolverInterface $resourceClassResolver = null, ?array $distinctFormats = null, private ?DefinitionNameFactoryInterface $definitionNameFactory = null)
    {
        if (!$definitionNameFactory) {
            $this->definitionNameFactory = new DefinitionNameFactory($distinctFormats);
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $schema ? clone $schema : new Schema();

        if (!$this->isResourceClass($className)) {
            $operation = null;
            $inputOrOutputClass = $className;
            $serializerContext ??= [];
        } else {
            $operation = $this->findOperation($className, $type, $operation, $serializerContext, $format);
            $inputOrOutputClass = $this->findOutputClass($className, $type, $operation, $serializerContext);
            $serializerContext ??= $this->getSerializerContext($operation, $type);
        }

        if (null === $inputOrOutputClass) {
            // input or output disabled
            return $schema;
        }

        $validationGroups = $operation ? $this->getValidationGroups($operation) : [];
        $version = $schema->getVersion();
        $definitionName = $this->definitionNameFactory->create($className, $format, $inputOrOutputClass, $operation, $serializerContext);
        $method = $operation instanceof HttpOperation ? $operation->getMethod() : 'GET';
        if (!$operation) {
            $method = Schema::TYPE_INPUT === $type ? 'POST' : 'GET';
        }

        // In case of FORCE_SUBSCHEMA an object can be writable through another class even though it has no POST operation
        if (!($serializerContext[self::FORCE_SUBSCHEMA] ?? false) && Schema::TYPE_OUTPUT !== $type && !\in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            return $schema;
        }

        if (!isset($schema['$ref']) && !isset($schema['type'])) {
            $ref = $this->getSchemaUriPrefix($version).$definitionName;
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
        if ($description = $operation?->getDescription()) {
            $definition['description'] = $description;
        }

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

        $options = ['schema_type' => $type] + $this->getFactoryOptions($serializerContext, $validationGroups, $operation instanceof HttpOperation ? $operation : null);
        foreach ($this->propertyNameCollectionFactory->create($inputOrOutputClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($inputOrOutputClass, $propertyName, $options);

            if (false === $propertyMetadata->isReadable() && false === $propertyMetadata->isWritable()) {
                continue;
            }

            $normalizedPropertyName = $this->nameConverter ? $this->nameConverter->normalize($propertyName, $inputOrOutputClass, $format, $serializerContext) : $propertyName;
            if ($propertyMetadata->isRequired()) {
                $definition['required'][] = $normalizedPropertyName;
            }

            if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
                $this->buildLegacyPropertySchema($schema, $definitionName, $normalizedPropertyName, $propertyMetadata, $serializerContext, $format, $type);
            } else {
                $this->buildPropertySchema($schema, $definitionName, $normalizedPropertyName, $propertyMetadata, $serializerContext, $format, $type);
            }
        }

        return $schema;
    }

    /**
     * Builds the JSON Schema for a property using the legacy PropertyInfo component.
     */
    private function buildLegacyPropertySchema(Schema $schema, string $definitionName, string $normalizedPropertyName, ApiProperty $propertyMetadata, array $serializerContext, string $format, string $parentType): void
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

        // @see https://github.com/api-platform/core/issues/6299
        if (Schema::UNKNOWN_TYPE === ($propertySchema['type'] ?? null) && isset($propertySchema['$ref'])) {
            unset($propertySchema['type']);
        }

        $extraProperties = $propertyMetadata->getExtraProperties();
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
            || ('array' === $propertySchemaType && Schema::UNKNOWN_TYPE === ($propertySchema['items']['type'] ?? null))
            || ('object' === $propertySchemaType && Schema::UNKNOWN_TYPE === ($propertySchema['additionalProperties']['type'] ?? null));

        // Scalar properties
        if (
            !$isUnknown && (
                [] === $types
                || ($propertySchema['$ref'] ?? $propertySchema['anyOf'] ?? $propertySchema['allOf'] ?? $propertySchema['oneOf'] ?? false)
                || (\is_array($propertySchemaType) ? \array_key_exists('string', $propertySchemaType) : 'string' !== $propertySchemaType)
                || ($propertySchema['format'] ?? $propertySchema['enum'] ?? false)
            )
        ) {
            if (isset($propertySchema['$ref'])) {
                unset($propertySchema['type']);
            }

            $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);

            return;
        }

        // property schema is created in SchemaPropertyMetadataFactory, but it cannot build resource reference ($ref)
        // complete property schema with resource reference ($ref) only if it's related to an object
        $version = $schema->getVersion();
        $refs = [];
        $isNullable = null;

        foreach ($types as $type) {
            $subSchema = new Schema($version);
            $subSchema->setDefinitions($schema->getDefinitions()); // Populate definitions of the main schema

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

            if (false === $propertyMetadata->getGenId()) {
                $subDefinitionName = $this->definitionNameFactory->create($className, $format, $className, null, $serializerContext);

                if (isset($subSchema->getDefinitions()[$subDefinitionName])) {
                    // @see https://github.com/api-platform/core/issues/7162
                    // Need to rebuild the definition without @id property and set it back to the sub-schema
                    $subSchemaDefinition = $subSchema->getDefinitions()[$subDefinitionName]->getArrayCopy();
                    unset($subSchemaDefinition['properties']['@id']);
                    $subSchema->getDefinitions()[$subDefinitionName] = new \ArrayObject($subSchemaDefinition);
                }
            }

            if ($isCollection) {
                $key = ($propertySchema['type'] ?? null) === 'object' ? 'additionalProperties' : 'items';
                $propertySchema[$key]['$ref'] = $subSchema['$ref'];
                unset($propertySchema[$key]['type']);
                break;
            }

            $refs[] = ['$ref' => $subSchema['$ref']];
            $isNullable = $isNullable ?? $type->isNullable();
        }

        if ($isNullable) {
            $refs[] = ['type' => 'null'];
        }

        $c = \count($refs);
        if ($c > 1) {
            $propertySchema['anyOf'] = $refs;
            unset($propertySchema['type']);
        } elseif (1 === $c) {
            $propertySchema['$ref'] = $refs[0]['$ref'];
            unset($propertySchema['type']);
        }

        $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);
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

        $extraProperties = $propertyMetadata->getExtraProperties();
        // see AttributePropertyMetadataFactory
        if (true === ($extraProperties[SchemaPropertyMetadataFactory::JSON_SCHEMA_USER_DEFINED] ?? false)) {
            // schema seems to have been declared by the user: do not override nor complete user value
            $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);

            return;
        }

        $type = $propertyMetadata->getNativeType();

        // Type is defined in an allOf, anyOf, or oneOf
        $propertySchemaType = $this->getSchemaValue($propertySchema, 'type');
        $currentRef = $this->getSchemaValue($propertySchema, '$ref');
        $isSchemaDefined = null !== ($currentRef ?? $this->getSchemaValue($propertySchema, 'format') ?? $this->getSchemaValue($propertySchema, 'enum'));
        if (!$isSchemaDefined && Schema::UNKNOWN_TYPE !== $propertySchemaType) {
            $isSchemaDefined = true;
        }

        // Check if the type is considered "unknown" by SchemaPropertyMetadataFactory
        if (isset($propertySchema['additionalProperties']['type']) && Schema::UNKNOWN_TYPE === $propertySchema['additionalProperties']['type']) {
            $isSchemaDefined = false;
        }

        if ($isSchemaDefined && Schema::UNKNOWN_TYPE !== $propertySchemaType) {
            // If schema is defined and not marked as unknown, or if no type info exists, return early
            $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);

            return;
        }

        if (Schema::UNKNOWN_TYPE === $propertySchemaType) {
            $propertySchema = [];
        }

        // property schema is created in SchemaPropertyMetadataFactory, but it cannot build resource reference ($ref)
        // complete property schema with resource reference ($ref) if it's related to an object/resource
        $refs = [];
        $isNullable = $type?->isNullable() ?? false;

        // TODO: refactor this with TypeInfo we shouldn't have to loop like this, the below code handles object refs
        if ($type) {
            foreach ($type instanceof CompositeTypeInterface ? $type->getTypes() : [$type] as $t) {
                if ($t instanceof BuiltinType && TypeIdentifier::NULL === $t->getTypeIdentifier()) {
                    continue;
                }

                $valueType = $t;
                $isCollection = $t instanceof CollectionType;

                if ($isCollection) {
                    $valueType = TypeHelper::getCollectionValueType($t);
                }

                if (!$valueType instanceof ObjectType) {
                    continue;
                }

                $className = $valueType->getClassName();
                $subSchemaInstance = new Schema($version);
                $subSchemaInstance->setDefinitions($schema->getDefinitions());
                $subSchemaFactory = $this->schemaFactory ?: $this;
                $subSchemaResult = $subSchemaFactory->buildSchema($className, $format, $parentType, null, $subSchemaInstance, $serializerContext + [self::FORCE_SUBSCHEMA => true], false);
                if (!isset($subSchemaResult['$ref'])) {
                    continue;
                }

                if (false === $propertyMetadata->getGenId()) {
                    $subDefinitionName = $this->definitionNameFactory->create($className, $format, $className, null, $serializerContext);
                    if (isset($subSchemaResult->getDefinitions()[$subDefinitionName]['properties']['@id'])) {
                        unset($subSchemaResult->getDefinitions()[$subDefinitionName]['properties']['@id']);
                    }
                }

                if ($isCollection) {
                    $key = ($propertySchema['type'] ?? null) === 'object' ? 'additionalProperties' : 'items';
                    if (!isset($propertySchema['type'])) {
                        $propertySchema['type'] = 'array';
                    }

                    if (!isset($propertySchema[$key]) || !\is_array($propertySchema[$key])) {
                        $propertySchema[$key] = [];
                    }
                    $propertySchema[$key] = ['$ref' => $subSchemaResult['$ref']];
                    $refs = [];
                    break;
                }

                $refs[] = ['$ref' => $subSchemaResult['$ref']];
            }
        }

        if (!empty($refs)) {
            if ($isNullable) {
                $refs[] = ['type' => 'null'];
            }

            if (($c = \count($refs)) > 1) {
                $propertySchema = ['anyOf' => $refs];
            } elseif (1 === $c) {
                $propertySchema = ['$ref' => $refs[0]['$ref']];
            }
        }

        if (null !== $propertyMetadata->getUriTemplate() || (!\array_key_exists('readOnly', $propertySchema) && false === $propertyMetadata->isWritable() && !$propertyMetadata->isInitializable()) && !isset($propertySchema['$ref'])) {
            $propertySchema['readOnly'] = true;
        }

        $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = new \ArrayObject($propertySchema);
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

        if ($operation && ($ignoredAttributes = $operation->getNormalizationContext()['ignored_attributes'] ?? null)) {
            $options['ignored_attributes'] = $ignoredAttributes;
        }

        return $options;
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        $this->schemaFactory = $schemaFactory;
    }

    private function getSchemaValue(array $schema, string $key): array|string|null
    {
        if (isset($schema['items'])) {
            $schema = $schema['items'];
        }

        return $schema[$key] ?? $schema['allOf'][0][$key] ?? $schema['anyOf'][0][$key] ?? $schema['oneOf'][0][$key] ?? null;
    }
}
