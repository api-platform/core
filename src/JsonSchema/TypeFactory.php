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
            $subType = new Type($type->getBuiltinType(), $type->isNullable(), $type->getClassName(), false);

            return [
                'type' => 'array',
                'items' => $this->getType($subType, $format, $readableLink, $serializerContext, $schema),
            ];
        }

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
    private function getClassType(?string $className, string $format = 'json', ?bool $readableLink = null, ?array $serializerContext = null, ?Schema $schema = null): array
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
}
