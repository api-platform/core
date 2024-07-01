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

namespace ApiPlatform\JsonSchema\Metadata\Property\Factory;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * Build ApiProperty::schema.
 */
final class SchemaPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use ResourceClassInfoTrait;

    public const JSON_SCHEMA_USER_DEFINED = 'user_defined_schema';

    public function __construct(ResourceClassResolverInterface $resourceClassResolver, private readonly ?PropertyMetadataFactoryInterface $decorated = null)
    {
        $this->resourceClassResolver = $resourceClassResolver;
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (null === $this->decorated) {
            $propertyMetadata = new ApiProperty();
        } else {
            try {
                $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);
            } catch (PropertyNotFoundException) {
                $propertyMetadata = new ApiProperty();
            }
        }

        $extraProperties = $propertyMetadata->getExtraProperties() ?? [];
        // see AttributePropertyMetadataFactory
        if (true === ($extraProperties[self::JSON_SCHEMA_USER_DEFINED] ?? false)) {
            // schema seems to have been declared by the user: do not override nor complete user value
            return $propertyMetadata;
        }

        $link = (($options['schema_type'] ?? null) === Schema::TYPE_INPUT) ? $propertyMetadata->isWritableLink() : $propertyMetadata->isReadableLink();
        $propertySchema = $propertyMetadata->getSchema() ?? [];

        if (null !== $propertyMetadata->getUriTemplate() || (!\array_key_exists('readOnly', $propertySchema) && false === $propertyMetadata->isWritable() && !$propertyMetadata->isInitializable())) {
            $propertySchema['readOnly'] = true;
        }

        if (!\array_key_exists('writeOnly', $propertySchema) && false === $propertyMetadata->isReadable()) {
            $propertySchema['writeOnly'] = true;
        }

        if (!\array_key_exists('description', $propertySchema) && null !== ($description = $propertyMetadata->getDescription())) {
            $propertySchema['description'] = $description;
        }

        // see https://github.com/json-schema-org/json-schema-spec/pull/737
        if (!\array_key_exists('deprecated', $propertySchema) && null !== $propertyMetadata->getDeprecationReason()) {
            $propertySchema['deprecated'] = true;
        }

        // externalDocs is an OpenAPI specific extension, but JSON Schema allows additional keys, so we always add it
        // See https://json-schema.org/latest/json-schema-core.html#rfc.section.6.4
        if (!\array_key_exists('externalDocs', $propertySchema) && null !== ($iri = $propertyMetadata->getTypes()[0] ?? null)) {
            $propertySchema['externalDocs'] = ['url' => $iri];
        }

        $types = $propertyMetadata->getBuiltinTypes() ?? [];

        if (!\array_key_exists('default', $propertySchema) && !empty($default = $propertyMetadata->getDefault()) && (!\count($types) || null === ($className = $types[0]->getClassName()) || !$this->isResourceClass($className))) {
            if ($default instanceof \BackedEnum) {
                $default = $default->value;
            }
            $propertySchema['default'] = $default;
        }

        if (!\array_key_exists('example', $propertySchema) && !empty($example = $propertyMetadata->getExample())) {
            $propertySchema['example'] = $example;
        }

        if (!\array_key_exists('example', $propertySchema) && \array_key_exists('default', $propertySchema)) {
            $propertySchema['example'] = $propertySchema['default'];
        }

        // never override the following keys if at least one is already set or if there's a custom openapi context
        if ([] === $types
            || ($propertySchema['type'] ?? $propertySchema['$ref'] ?? $propertySchema['anyOf'] ?? $propertySchema['allOf'] ?? $propertySchema['oneOf'] ?? false)
            || ($propertyMetadata->getOpenapiContext() ?? false)
        ) {
            return $propertyMetadata->withSchema($propertySchema);
        }

        $valueSchema = [];
        foreach ($types as $type) {
            // Temp fix for https://github.com/symfony/symfony/pull/52699
            if (ArrayCollection::class === $type->getClassName()) {
                $type = new Type($type->getBuiltinType(), $type->isNullable(), $type->getClassName(), true, $type->getCollectionKeyTypes(), $type->getCollectionValueTypes());
            }

            if ($isCollection = $type->isCollection()) {
                $keyType = $type->getCollectionKeyTypes()[0] ?? null;
                $valueType = $type->getCollectionValueTypes()[0] ?? null;
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

            if ($isCollection && null !== $propertyMetadata->getUriTemplate()) {
                $keyType = null;
                $isCollection = false;
            }

            $propertyType = $this->getType(new Type($builtinType, $type->isNullable(), $className, $isCollection, $keyType, $valueType), $link);
            if (!\in_array($propertyType, $valueSchema, true)) {
                $valueSchema[] = $propertyType;
            }
        }

        // only one builtInType detected (should be "type" or "$ref")
        if (1 === \count($valueSchema)) {
            return $propertyMetadata->withSchema($propertySchema + $valueSchema[0]);
        }

        // multiple builtInTypes detected: determine oneOf/allOf if union vs intersect types
        try {
            $reflectionClass = new \ReflectionClass($resourceClass);
            $reflectionProperty = $reflectionClass->getProperty($property);
            $composition = $reflectionProperty->getType() instanceof \ReflectionUnionType ? 'oneOf' : 'allOf';
        } catch (\ReflectionException) {
            // cannot detect types
            $composition = 'anyOf';
        }

        return $propertyMetadata->withSchema($propertySchema + [$composition => $valueSchema]);
    }

    private function getType(Type $type, ?bool $readableLink = null): array
    {
        if (!$type->isCollection()) {
            return $this->addNullabilityToTypeDefinition($this->typeToArray($type, $readableLink), $type);
        }

        $keyType = $type->getCollectionKeyTypes()[0] ?? null;
        $subType = ($type->getCollectionValueTypes()[0] ?? null) ?? new Type($type->getBuiltinType(), false, $type->getClassName(), false);

        if (null !== $keyType && Type::BUILTIN_TYPE_STRING === $keyType->getBuiltinType()) {
            return $this->addNullabilityToTypeDefinition([
                'type' => 'object',
                'additionalProperties' => $this->getType($subType, $readableLink),
            ], $type);
        }

        return $this->addNullabilityToTypeDefinition([
            'type' => 'array',
            'items' => $this->getType($subType, $readableLink),
        ], $type);
    }

    private function typeToArray(Type $type, ?bool $readableLink = null): array
    {
        return match ($type->getBuiltinType()) {
            Type::BUILTIN_TYPE_INT => ['type' => 'integer'],
            Type::BUILTIN_TYPE_FLOAT => ['type' => 'number'],
            Type::BUILTIN_TYPE_BOOL => ['type' => 'boolean'],
            Type::BUILTIN_TYPE_OBJECT => $this->getClassType($type->getClassName(), $type->isNullable(), $readableLink),
            default => ['type' => 'string'],
        };
    }

    /**
     * Gets the JSON Schema document which specifies the data type corresponding to the given PHP class, and recursively adds needed new schema to the current schema if provided.
     *
     * Note: if the class is not part of exceptions listed above, any class is considered as a resource.
     */
    private function getClassType(?string $className, bool $nullable, ?bool $readableLink): array
    {
        if (null === $className) {
            return ['type' => 'string'];
        }

        if (is_a($className, \DateTimeInterface::class, true)) {
            return [
                'type' => 'string',
                'format' => 'date-time',
            ];
        }

        if (is_a($className, \DateInterval::class, true)) {
            return [
                'type' => 'string',
                'format' => 'duration',
            ];
        }

        if (is_a($className, UuidInterface::class, true) || is_a($className, Uuid::class, true)) {
            return [
                'type' => 'string',
                'format' => 'uuid',
            ];
        }

        if (is_a($className, Ulid::class, true)) {
            return [
                'type' => 'string',
                'format' => 'ulid',
            ];
        }

        if (is_a($className, \SplFileInfo::class, true)) {
            return [
                'type' => 'string',
                'format' => 'binary',
            ];
        }

        if (!$this->isResourceClass($className) && is_a($className, \BackedEnum::class, true)) {
            $enumCases = array_map(static fn (\BackedEnum $enum): string|int => $enum->value, $className::cases());

            $type = \is_string($enumCases[0] ?? '') ? 'string' : 'integer';

            if ($nullable) {
                $enumCases[] = null;
            }

            return [
                'type' => $type,
                'enum' => $enumCases,
            ];
        }

        if (true !== $readableLink && $this->isResourceClass($className)) {
            return [
                'type' => 'string',
                'format' => 'iri-reference',
                'example' => 'https://example.com/',
            ];
        }

        // TODO: add propertyNameCollectionFactory and recurse to find the underlying schema? Right now SchemaFactory does the job so we don't compute anything here.
        return ['type' => Schema::UNKNOWN_TYPE];
    }

    /**
     * @param array<string, mixed> $jsonSchema
     *
     * @return array<string, mixed>
     */
    private function addNullabilityToTypeDefinition(array $jsonSchema, Type $type): array
    {
        if (!$type->isNullable()) {
            return $jsonSchema;
        }

        if (\array_key_exists('$ref', $jsonSchema)) {
            return ['anyOf' => [$jsonSchema, 'type' => 'null']];
        }

        return [...$jsonSchema, ...[
            'type' => \is_array($jsonSchema['type'])
                ? array_merge($jsonSchema['type'], ['null'])
                : [$jsonSchema['type'], 'null'],
        ]];
    }
}
