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
     * Recursively resolve $ref, allOf, and nested structures into a flat schema node.
     *
     * @param array $resolving Tracks the current $ref resolution chain to detect circular references
     */
    public static function resolveNode(array|\ArrayObject $node, array $definitions, array &$resolving = []): array
    {
        if ($node instanceof \ArrayObject) {
            $node = $node->getArrayCopy();
        }

        if (isset($node['$ref'])) {
            $refKey = str_replace('#/definitions/', '', $node['$ref']);
            if (!isset($definitions[$refKey]) || isset($resolving[$refKey])) {
                return ['type' => 'object'];
            }
            $resolving[$refKey] = true;
            $resolved = self::resolveNode($definitions[$refKey], $definitions, $resolving);
            unset($resolving[$refKey]);

            return $resolved;
        }

        if (isset($node['allOf'])) {
            $merged = ['type' => 'object', 'properties' => []];
            $requiredSets = [];
            foreach ($node['allOf'] as $entry) {
                $resolved = self::resolveNode($entry, $definitions, $resolving);
                if (isset($resolved['properties'])) {
                    foreach ($resolved['properties'] as $k => $v) {
                        $merged['properties'][$k] = $v;
                    }
                }
                if (isset($resolved['required'])) {
                    $requiredSets[] = $resolved['required'];
                }
            }

            if ($requiredSets) {
                $merged['required'] = array_merge(...$requiredSets);
            }
            if ([] === $merged['properties']) {
                unset($merged['properties']);
            }
            if (isset($node['description'])) {
                $merged['description'] = $node['description'];
            }

            return self::resolveDeep($merged, $definitions, $resolving);
        }

        if (!isset($node['type'])) {
            $node['type'] = 'object';
        }

        return self::resolveDeep($node, $definitions, $resolving);
    }

    /**
     * Recursively resolve nested properties and array items.
     */
    private static function resolveDeep(array $node, array $definitions, array &$resolving): array
    {
        if (isset($node['items'])) {
            $node['items'] = self::resolveNode(
                $node['items'] instanceof \ArrayObject ? $node['items']->getArrayCopy() : $node['items'],
                $definitions,
                $resolving,
            );
        }

        if (isset($node['properties']) && \is_array($node['properties'])) {
            foreach ($node['properties'] as $propName => $propSchema) {
                $node['properties'][$propName] = self::resolveNode(
                    $propSchema instanceof \ArrayObject ? $propSchema->getArrayCopy() : $propSchema,
                    $definitions,
                    $resolving,
                );
            }
        }

        return $node;
    }
}
