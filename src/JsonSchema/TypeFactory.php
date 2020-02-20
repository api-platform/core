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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * {@inheritdoc}
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class TypeFactory implements TypeFactoryInterface
{
    use ResourceClassInfoTrait;

    /**
     * This constant is to be provided as serializer context key to conditionally enable types to be generated in
     * a format that is compatible with OpenAPI specifications **PREVIOUS** to 3.0.
     *
     * Without this flag being set, the generated format will only be compatible with Swagger 3.0 or newer.
     *
     * Once support for OpenAPI < 3.0 is gone, this constant **WILL BE REMOVED**
     *
     * @internal Once support for OpenAPI < 3.0 is gone, this constant **WILL BE REMOVED** - do not rely on
     *           it in downstream projects!
     */
    public const CONTEXT_SERIALIZATION_FORMAT_OPENAPI_PRE_V3_0 = self::class.'::CONTEXT_SERIALIZATION_FORMAT_OPENAPI_PRE_V3_0';

    /**
     * @var SchemaFactoryInterface|null
     */
    private $schemaFactory;

    public function __construct(ResourceClassResolverInterface $resourceClassResolver = null)
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
    public function getType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, Schema $schema = null): array
    {
        if ($type->isCollection()) {
            $keyType = $type->getCollectionKeyType();
            $subType = $type->getCollectionValueType()
                ?? new Type($type->getBuiltinType(), false, $type->getClassName(), false);

            if (null !== $keyType && Type::BUILTIN_TYPE_STRING === $keyType->getBuiltinType()) {
                return $this->addNullabilityToTypeDefinition(
                    [
                        'type' => 'object',
                        'additionalProperties' => $this->getType($subType, $format, $readableLink, $serializerContext, $schema),
                    ],
                    $type,
                    (array) $serializerContext
                );
            }

            return $this->addNullabilityToTypeDefinition(
                [
                    'type' => 'array',
                    'items' => $this->getType($subType, $format, $readableLink, $serializerContext, $schema),
                ],
                $type,
                (array) $serializerContext
            );
        }

        return $this->addNullabilityToTypeDefinition(
            $this->makeBasicType($type, $format, $readableLink, $serializerContext, $schema),
            $type,
            (array) $serializerContext
        );
    }

    private function makeBasicType(Type $type, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, Schema $schema = null): array
    {
        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_INT:
                return ['type' => 'integer'];
            case Type::BUILTIN_TYPE_FLOAT:
                return ['type' => 'number'];
            case Type::BUILTIN_TYPE_BOOL:
                return ['type' => 'boolean'];
            case Type::BUILTIN_TYPE_OBJECT:
                return $this->getClassType($type->getClassName(), $format, $readableLink, $serializerContext, $schema);
            default:
                return ['type' => 'string'];
        }
    }

    /**
     * Gets the JSON Schema document which specifies the data type corresponding to the given PHP class, and recursively adds needed new schema to the current schema if provided.
     */
    private function getClassType(?string $className, string $format, ?bool $readableLink, ?array $serializerContext, ?Schema $schema): array
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
        if (is_a($className, UuidInterface::class, true)) {
            return [
                'type' => 'string',
                'format' => 'uuid',
            ];
        }

        // Skip if $schema is null (filters only support basic types)
        if (null === $schema) {
            return ['type' => 'string'];
        }

        if ($this->isResourceClass($className) && true !== $readableLink) {
            return [
                'type' => 'string',
                'format' => 'iri-reference',
            ];
        }

        $version = $schema->getVersion();

        $subSchema = new Schema($version);
        $subSchema->setDefinitions($schema->getDefinitions()); // Populate definitions of the main schema

        if (null === $this->schemaFactory) {
            throw new \LogicException('The schema factory must be injected by calling the "setSchemaFactory" method.');
        }

        $subSchema = $this->schemaFactory->buildSchema($className, $format, Schema::TYPE_OUTPUT, null, null, $subSchema, $serializerContext);

        return ['$ref' => $subSchema['$ref']];
    }

    /**
     * @param array<string, mixed> $jsonSchema
     *
     * @return array<string, mixed>
     */
    private function addNullabilityToTypeDefinition(array $jsonSchema, Type $type, array $serializerContext): array
    {
        if (\array_key_exists(self::CONTEXT_SERIALIZATION_FORMAT_OPENAPI_PRE_V3_0, $serializerContext)) {
            return $jsonSchema;
        }

        if (!$type->isNullable()) {
            return $jsonSchema;
        }

        if (\array_key_exists('$ref', $jsonSchema)) {
            return [
                'nullable' => true,
                'anyOf' => [$jsonSchema],
            ];
        }

        return array_merge($jsonSchema, ['nullable' => true]);
    }
}
