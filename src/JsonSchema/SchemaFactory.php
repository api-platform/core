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
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
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
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $typeFactory;
    private $nameConverter;
    private $distinctFormats = [];

    public function __construct(TypeFactoryInterface $typeFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, NameConverterInterface $nameConverter = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->nameConverter = $nameConverter;
        $this->typeFactory = $typeFactory;
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
    public function buildSchema(string $resourceClass, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $schema ?? new Schema();
        if (null === $metadata = $this->getMetadata($resourceClass, $type, $operationType, $operationName, $serializerContext)) {
            return $schema;
        }
        [$resourceMetadata, $serializerContext, $inputOrOutputClass] = $metadata;

        $version = $schema->getVersion();
        $definitionName = $this->buildDefinitionName($resourceClass, $format, $type, $operationType, $operationName, $serializerContext);

        $method = (null !== $operationType && null !== $operationName) ? $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'method') : 'GET';

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

        $definition = new \ArrayObject(['type' => 'object']);
        $definitions[$definitionName] = $definition;
        if (null !== $description = $resourceMetadata->getDescription()) {
            $definition['description'] = $description;
        }
        // see https://github.com/json-schema-org/json-schema-spec/pull/737
        if (
            Schema::VERSION_SWAGGER !== $version &&
            (
                (null !== $operationType && null !== $operationName && null !== $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, 'deprecation_reason', null, true)) ||
                null !== $resourceMetadata->getAttribute('deprecation_reason', null)
            )
        ) {
            $definition['deprecated'] = true;
        }
        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        if (null !== $iri = $resourceMetadata->getIri()) {
            $definition['externalDocs'] = ['url' => $iri];
        }

        $options = isset($serializerContext[AbstractNormalizer::GROUPS]) ? ['serializer_groups' => (array) $serializerContext[AbstractNormalizer::GROUPS]] : [];
        foreach ($this->propertyNameCollectionFactory->create($inputOrOutputClass, $options) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($inputOrOutputClass, $propertyName);
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

        $propertySchema = $propertyMetadata->getAttributes()[$basePropertySchemaAttribute] ?? [];
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

        $valueSchema = [];
        if (null !== $type = $propertyMetadata->getType()) {
            $isCollection = $type->isCollection();
            if (null === $valueType = $isCollection ? $type->getCollectionValueType() : $type) {
                $builtinType = 'string';
                $className = null;
            } else {
                $builtinType = $valueType->getBuiltinType();
                $className = $valueType->getClassName();
            }

            $valueSchema = $this->typeFactory->getType(new Type($builtinType, $type->isNullable(), $className, $isCollection), $format, $propertyMetadata->isReadableLink(), $serializerContext, $schema);
        }

        $propertySchema = new \ArrayObject($propertySchema + $valueSchema);
        if (DocumentationNormalizer::OPENAPI_VERSION === $version) {
            $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = $propertySchema;

            return;
        }

        $schema->getDefinitions()[$definitionName]['properties'][$normalizedPropertyName] = $propertySchema;
    }

    private function buildDefinitionName(string $resourceClass, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?array $serializerContext = null): string
    {
        [$resourceMetadata, $serializerContext, $inputOrOutputClass] = $this->getMetadata($resourceClass, $type, $operationType, $operationName, $serializerContext);

        $prefix = $resourceMetadata->getShortName();
        if (null !== $inputOrOutputClass && $resourceClass !== $inputOrOutputClass) {
            $prefix .= ':'.md5($inputOrOutputClass);
        }

        if (isset($this->distinctFormats[$format])) {
            // JSON is the default, and so isn't included in the definition name
            $prefix .= ':'.$format;
        }

        if (isset($serializerContext[DocumentationNormalizer::SWAGGER_DEFINITION_NAME])) {
            $name = sprintf('%s-%s', $prefix, $serializerContext[DocumentationNormalizer::SWAGGER_DEFINITION_NAME]);
        } else {
            $groups = (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []);
            $name = $groups ? sprintf('%s-%s', $prefix, implode('_', $groups)) : $prefix;
        }

        return $name;
    }

    private function getMetadata(string $resourceClass, string $type = Schema::TYPE_OUTPUT, ?string $operationType, ?string $operationName, ?array $serializerContext): ?array
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attribute = Schema::TYPE_OUTPUT === $type ? 'output' : 'input';
        if (null === $operationType || null === $operationName) {
            $inputOrOutput = $resourceMetadata->getAttribute($attribute, ['class' => $resourceClass]);
        } else {
            $inputOrOutput = $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, $attribute, ['class' => $resourceClass], true);
        }

        if (false === ($inputOrOutput['class'] ?? false)) {
            // input or output disabled
            return null;
        }

        return [
            $resourceMetadata,
            $serializerContext ?? $this->getSerializerContext($resourceMetadata, $type, $operationType, $operationName),
            $inputOrOutput['class'],
        ];
    }

    private function getSerializerContext(ResourceMetadata $resourceMetadata, string $type = Schema::TYPE_OUTPUT, ?string $operationType, ?string $operationName): array
    {
        $attribute = Schema::TYPE_OUTPUT === $type ? 'normalization_context' : 'denormalization_context';

        if (null === $operationType || null === $operationName) {
            return $resourceMetadata->getAttribute($attribute, []);
        }

        return $resourceMetadata->getTypedOperationAttribute($operationType, $operationName, $attribute, [], true);
    }
}
