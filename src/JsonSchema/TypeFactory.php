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

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

/**
 * {@inheritdoc}
 *
 * @deprecated since 3.3 https://github.com/api-platform/core/pull/5470
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class TypeFactory implements TypeFactoryInterface
{
    use ResourceClassInfoTrait;

    private ?SchemaFactoryInterface $schemaFactory = null;

    public function __construct(?ResourceClassResolverInterface $resourceClassResolver = null)
    {
        $this->resourceClassResolver = $resourceClassResolver;
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        $this->schemaFactory = $schemaFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, ?Schema $schema = null): array
    {
        if ('jsonschema' === $format) {
            return [];
        }

        // TODO: OpenApiFactory uses this to compute filter types
        if ($type->isCollection()) {
            $keyType = $type->getCollectionKeyTypes()[0] ?? null;
            $subType = ($type->getCollectionValueTypes()[0] ?? null) ?? new Type($type->getBuiltinType(), false, $type->getClassName(), false);

            if (null !== $keyType && Type::BUILTIN_TYPE_STRING === $keyType->getBuiltinType()) {
                return $this->addNullabilityToTypeDefinition([
                    'type' => 'object',
                    'additionalProperties' => $this->getType($subType, $format, $readableLink, $serializerContext, $schema),
                ], $type, $schema);
            }

            return $this->addNullabilityToTypeDefinition([
                'type' => 'array',
                'items' => $this->getType($subType, $format, $readableLink, $serializerContext, $schema),
            ], $type, $schema);
        }

        return $this->addNullabilityToTypeDefinition($this->makeBasicType($type, $format, $readableLink, $serializerContext, $schema), $type, $schema);
    }

    private function makeBasicType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, ?Schema $schema = null): array
    {
        return match ($type->getBuiltinType()) {
            Type::BUILTIN_TYPE_INT => ['type' => 'integer'],
            Type::BUILTIN_TYPE_FLOAT => ['type' => 'number'],
            Type::BUILTIN_TYPE_BOOL => ['type' => 'boolean'],
            Type::BUILTIN_TYPE_OBJECT => $this->getClassType($type->getClassName(), $type->isNullable(), $format, $readableLink, $serializerContext, $schema),
            default => ['type' => 'string'],
        };
    }

    /**
     * Gets the JSON Schema document which specifies the data type corresponding to the given PHP class, and recursively adds needed new schema to the current schema if provided.
     */
    private function getClassType(?string $className, bool $nullable, string $format, ?bool $readableLink, ?array $serializerContext, ?Schema $schema): array
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

        // Skip if $schema is null (filters only support basic types)
        if (null === $schema) {
            return ['type' => 'string'];
        }

        if (true !== $readableLink && $this->isResourceClass($className)) {
            return [
                'type' => 'string',
                'format' => 'iri-reference',
                'example' => 'https://example.com/',
            ];
        }

        $version = $schema->getVersion();

        $subSchema = new Schema($version);
        $subSchema->setDefinitions($schema->getDefinitions()); // Populate definitions of the main schema

        if (null === $this->schemaFactory) {
            throw new \LogicException('The schema factory must be injected by calling the "setSchemaFactory" method.');
        }

        $serializerContext += [SchemaFactory::FORCE_SUBSCHEMA => true];
        $subSchema = $this->schemaFactory->buildSchema($className, $format, Schema::TYPE_OUTPUT, null, $subSchema, $serializerContext, false);

        return ['$ref' => $subSchema['$ref']];
    }

    /**
     * @param array<string, mixed> $jsonSchema
     *
     * @return array<string, mixed>
     */
    private function addNullabilityToTypeDefinition(array $jsonSchema, Type $type, ?Schema $schema): array
    {
        if ($schema && Schema::VERSION_SWAGGER === $schema->getVersion()) {
            return $jsonSchema;
        }

        if (!$type->isNullable()) {
            return $jsonSchema;
        }

        if (\array_key_exists('$ref', $jsonSchema)) {
            $typeDefinition = ['anyOf' => [$jsonSchema]];

            if ($schema && Schema::VERSION_JSON_SCHEMA === $schema->getVersion()) {
                $typeDefinition['anyOf'][] = ['type' => 'null'];
            } else {
                // OpenAPI < 3.1
                $typeDefinition['nullable'] = true;
            }

            return $typeDefinition;
        }

        if ($schema && Schema::VERSION_JSON_SCHEMA === $schema->getVersion()) {
            return [...$jsonSchema, ...[
                'type' => \is_array($jsonSchema['type'])
                    ? array_merge($jsonSchema['type'], ['null'])
                    : [$jsonSchema['type'], 'null'],
            ]];
        }

        return [...$jsonSchema, ...['nullable' => true]];
    }
}
