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

namespace ApiPlatform\JsonApi\JsonSchema;

use ApiPlatform\Api\ResourceClassResolverInterface as LegacyResourceClassResolverInterface;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;

/**
 * Decorator factory which adds JSON:API properties to the JSON Schema document.
 *
 * @author Gwendolen Lynch <gwendolen.lynch@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    private const LINKS_PROPS = [
        'type' => 'object',
        'properties' => [
            'self' => [
                'type' => 'string',
                'format' => 'iri-reference',
            ],
            'first' => [
                'type' => 'string',
                'format' => 'iri-reference',
            ],
            'prev' => [
                'type' => 'string',
                'format' => 'iri-reference',
            ],
            'next' => [
                'type' => 'string',
                'format' => 'iri-reference',
            ],
            'last' => [
                'type' => 'string',
                'format' => 'iri-reference',
            ],
        ],
        'example' => [
            'self' => 'string',
            'first' => 'string',
            'prev' => 'string',
            'next' => 'string',
            'last' => 'string',
        ],
    ];
    private const META_PROPS = [
        'type' => 'object',
        'properties' => [
            'totalItems' => [
                'type' => 'integer',
                'minimum' => 0,
            ],
            'itemsPerPage' => [
                'type' => 'integer',
                'minimum' => 0,
            ],
            'currentPage' => [
                'type' => 'integer',
                'minimum' => 0,
            ],
        ],
    ];
    private const RELATION_PROPS = [
        'type' => 'object',
        'properties' => [
            'type' => [
                'type' => 'string',
            ],
            'id' => [
                'type' => 'string',
                'format' => 'iri-reference',
            ],
        ],
    ];
    private const PROPERTY_PROPS = [
        'id' => [
            'type' => 'string',
        ],
        'type' => [
            'type' => 'string',
        ],
        'attributes' => [
            'type' => 'object',
            'properties' => [],
        ],
    ];

    public function __construct(private readonly SchemaFactoryInterface $schemaFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, private readonly ResourceClassResolverInterface|LegacyResourceClassResolverInterface $resourceClassResolver)
    {
        if ($this->schemaFactory instanceof SchemaFactoryAwareInterface) {
            $this->schemaFactory->setSchemaFactory($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'jsonapi', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->schemaFactory->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
        if ('jsonapi' !== $format) {
            return $schema;
        }

        if (($key = $schema->getRootDefinitionKey()) || ($key = $schema->getItemsDefinitionKey())) {
            $definitions = $schema->getDefinitions();
            $properties = $definitions[$key]['properties'] ?? [];

            // Prevent reapplying
            if (isset($properties['id'], $properties['type']) || isset($properties['data'])) {
                return $schema;
            }

            $definitions[$key]['properties'] = $this->buildDefinitionPropertiesSchema($key, $className, $schema, $serializerContext);

            if ($schema->getRootDefinitionKey()) {
                return $schema;
            }
        }

        if (($schema['type'] ?? '') === 'array') {
            // data
            $items = $schema['items'];
            unset($schema['items']);

            $schema['type'] = 'object';
            $schema['properties'] = [
                'links' => self::LINKS_PROPS,
                'meta' => self::META_PROPS,
                'data' => [
                    'type' => 'array',
                    'items' => $items,
                ],
            ];
            $schema['required'] = [
                'data',
            ];

            return $schema;
        }

        return $schema;
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->schemaFactory instanceof SchemaFactoryAwareInterface) {
            $this->schemaFactory->setSchemaFactory($schemaFactory);
        }
    }

    private function buildDefinitionPropertiesSchema(string $key, string $className, Schema $schema, ?array $serializerContext): array
    {
        $definitions = $schema->getDefinitions();
        $properties = $definitions[$key]['properties'] ?? [];

        $attributes = [];
        $relationships = [];
        foreach ($properties as $propertyName => $property) {
            if ($relation = $this->getRelationship($className, $propertyName, $serializerContext)) {
                [$isOne, $isMany] = $relation;

                if ($isOne) {
                    $relationships[$propertyName]['properties']['data'] = self::RELATION_PROPS;
                    continue;
                }
                $relationships[$propertyName]['properties']['data'] = [
                    'type' => 'array',
                    'items' => self::RELATION_PROPS,
                ];
                continue;
            }
            if ('id' === $propertyName) {
                $attributes['_id'] = $property;
                continue;
            }
            $attributes[$propertyName] = $property;
        }

        $replacement = self::PROPERTY_PROPS;
        $replacement['attributes']['properties'] = $attributes;

        if (\count($relationships) > 0) {
            $replacement['relationships'] = [
                'type' => 'object',
                'properties' => $relationships,
            ];
        }

        if ($required = $definitions[$key]['required'] ?? null) {
            foreach ($required as $require) {
                if (isset($replacement['attributes']['properties'][$require])) {
                    $replacement['attributes']['required'][] = $require;
                    continue;
                }
                if (isset($relationships[$require])) {
                    $replacement['relationships']['required'][] = $require;
                }
            }
            unset($definitions[$key]['required']);
        }

        return [
            'data' => [
                'type' => 'object',
                'properties' => $replacement,
                'required' => ['type', 'id'],
            ],
        ];
    }

    private function getRelationship(string $resourceClass, string $property, ?array $serializerContext): ?array
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property, $serializerContext ?? []);
        $types = $propertyMetadata->getBuiltinTypes() ?? [];
        $isRelationship = false;
        $isOne = $isMany = false;

        foreach ($types as $type) {
            if ($type->isCollection()) {
                $collectionValueType = $type->getCollectionValueTypes()[0] ?? null;
                $isMany = $collectionValueType && ($className = $collectionValueType->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
            } else {
                $isOne = ($className = $type->getClassName()) && $this->resourceClassResolver->isResourceClass($className);
            }
            if (!isset($className) || (!$isOne && !$isMany)) {
                continue;
            }
            $isRelationship = true;
        }

        return $isRelationship ? [$isOne, $isMany] : null;
    }
}
