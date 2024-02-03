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

use ApiPlatform\Metadata\Operation;

/**
 * This factory decorates range integer and number properties to keep Draft 4 backward compatibility.
 *
 * @see https://github.com/api-platform/core/issues/6041
 *
 * @internal
 */
final class BackwardCompatibleSchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    public const SCHEMA_DRAFT4_VERSION = 'draft_4';

    public function __construct(private readonly SchemaFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->decorated->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);

        if (!($serializerContext[self::SCHEMA_DRAFT4_VERSION] ?? false)) {
            return $schema;
        }

        foreach ($schema->getDefinitions() as $definition) {
            foreach ($definition['properties'] ?? [] as $property) {
                if (isset($property['type']) && \in_array($property['type'], ['integer', 'number'], true)) {
                    if (isset($property['exclusiveMinimum'])) {
                        $property['minimum'] = $property['exclusiveMinimum'];
                        $property['exclusiveMinimum'] = true;
                    }
                    if (isset($property['exclusiveMaximum'])) {
                        $property['maximum'] = $property['exclusiveMaximum'];
                        $property['exclusiveMaximum'] = true;
                    }
                }
            }
        }

        return $schema;
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->decorated instanceof SchemaFactoryAwareInterface) {
            $this->decorated->setSchemaFactory($schemaFactory);
        }
    }
}
