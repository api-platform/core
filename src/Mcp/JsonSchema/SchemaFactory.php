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

namespace ApiPlatform\Mcp\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;

/**
 * Wraps a SchemaFactoryInterface and flattens the resulting schema
 * into a MCP-compliant structure: no $ref, no allOf, no definitions.
 *
 * @experimental
 */
final class SchemaFactory implements SchemaFactoryInterface
{
    public function __construct(
        private readonly SchemaFactoryInterface $decorated,
    ) {
    }

    public function buildSchema(string $className, string $format = 'json', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->decorated->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);

        $definitions = [];
        foreach ($schema->getDefinitions() as $key => $definition) {
            $definitions[$key] = $definition instanceof \ArrayObject ? $definition->getArrayCopy() : (array) $definition;
        }

        $rootKey = $schema->getRootDefinitionKey();
        if (null !== $rootKey) {
            $root = $definitions[$rootKey] ?? [];
        } else {
            // Collection schemas (and others) put allOf/type directly on the root
            $root = $schema->getArrayCopy(false);
        }

        $flat = self::resolveNode($root, $definitions);

        $flatSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($flatSchema['$schema']);
        foreach ($flat as $key => $value) {
            $flatSchema[$key] = $value;
        }

        return $flatSchema;
    }

    /**
     * Recursively resolve $ref and allOf into a flat schema node.
     */
    public static function resolveNode(array|\ArrayObject $node, array $definitions): array
    {
        if ($node instanceof \ArrayObject) {
            $node = $node->getArrayCopy();
        }

        if (isset($node['$ref'])) {
            $refKey = str_replace('#/definitions/', '', $node['$ref']);
            if (isset($definitions[$refKey])) {
                return self::resolveNode($definitions[$refKey], $definitions);
            }

            return ['type' => 'object'];
        }

        if (isset($node['allOf'])) {
            $merged = ['type' => 'object', 'properties' => [], 'required' => []];
            foreach ($node['allOf'] as $entry) {
                $resolved = self::resolveNode($entry, $definitions);
                if (isset($resolved['properties'])) {
                    $merged['properties'] = array_merge($merged['properties'], $resolved['properties']);
                }
                if (isset($resolved['required'])) {
                    $merged['required'] = array_merge($merged['required'], $resolved['required']);
                }
            }

            if ([] === $merged['required']) {
                unset($merged['required']);
            }
            if ([] === $merged['properties']) {
                unset($merged['properties']);
            }
            if (isset($node['description'])) {
                $merged['description'] = $node['description'];
            }

            return self::resolveProperties($merged, $definitions);
        }

        if (!isset($node['type'])) {
            $node['type'] = 'object';
        }

        return self::resolveProperties($node, $definitions);
    }

    /**
     * Recursively resolve $ref inside property schemas and array items.
     */
    private static function resolveProperties(array $node, array $definitions): array
    {
        if (isset($node['properties']) && \is_array($node['properties'])) {
            foreach ($node['properties'] as $propName => $propSchema) {
                $node['properties'][$propName] = self::resolvePropertyNode($propSchema, $definitions);
            }
        }

        return $node;
    }

    private static function resolvePropertyNode(array|\ArrayObject $node, array $definitions): array
    {
        if ($node instanceof \ArrayObject) {
            $node = $node->getArrayCopy();
        }

        if (isset($node['$ref'])) {
            $refKey = str_replace('#/definitions/', '', $node['$ref']);
            if (isset($definitions[$refKey])) {
                return self::resolveNode($definitions[$refKey], $definitions);
            }

            return ['type' => 'object'];
        }

        if (isset($node['items'])) {
            $node['items'] = self::resolvePropertyNode($node['items'], $definitions);
        }

        if (isset($node['properties']) && \is_array($node['properties'])) {
            foreach ($node['properties'] as $propName => $propSchema) {
                $node['properties'][$propName] = self::resolvePropertyNode($propSchema, $definitions);
            }
        }

        return $node;
    }
}
