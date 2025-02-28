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

use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\JsonSchema\ResourceMetadataTrait;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\SchemaUriPrefixTrait;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ApiResource\Error;

/**
 * Decorator factory which adds JSON:API properties to the JSON Schema document.
 *
 * @author Gwendolen Lynch <gwendolen.lynch@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    use ResourceMetadataTrait;
    use SchemaUriPrefixTrait;

    /**
     * As JSON:API recommends using [includes](https://jsonapi.org/format/#fetching-includes) instead of groups
     * this flag allows to force using groups to generate the JSON:API JSONSchema. Defaults to true, use it in
     * a serializer context.
     */
    public const DISABLE_JSON_SCHEMA_SERIALIZER_GROUPS = 'disable_json_schema_serializer_groups';

    private const COLLECTION_BASE_SCHEMA_NAME = 'JsonApiCollectionBaseSchema';

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

    public function __construct(private readonly SchemaFactoryInterface $schemaFactory, private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, private ?DefinitionNameFactoryInterface $definitionNameFactory = null)
    {
        if (!$definitionNameFactory) {
            $this->definitionNameFactory = new DefinitionNameFactory();
        }
        if ($this->schemaFactory instanceof SchemaFactoryAwareInterface) {
            $this->schemaFactory->setSchemaFactory($this);
        }
        $this->resourceClassResolver = $resourceClassResolver;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'jsonapi', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        if ('jsonapi' !== $format) {
            return $this->schemaFactory->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
        }

        if (!$this->isResourceClass($className)) {
            $operation = null;
            $inputOrOutputClass = null;
            $serializerContext ??= [];
        } else {
            $operation = $this->findOperation($className, $type, $operation, $serializerContext, $format);
            $inputOrOutputClass = $this->findOutputClass($className, $type, $operation, $serializerContext);
            $serializerContext ??= $this->getSerializerContext($operation, $type);
        }

        if (null === $inputOrOutputClass) {
            // input or output disabled
            return $this->schemaFactory->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
        }

        // We don't use the serializer context here as JSON:API doesn't leverage serializer groups for related resources.
        // That is done by query parameter. @see https://jsonapi.org/format/#fetching-includes
        $jsonApiSerializerContext = $serializerContext;
        if (false === ($serializerContext[self::DISABLE_JSON_SCHEMA_SERIALIZER_GROUPS] ?? true)) {
            unset($jsonApiSerializerContext['groups']);
        }

        $schema = $this->schemaFactory->buildSchema($className, 'json', $type, $operation, $schema, $jsonApiSerializerContext, $forceCollection);
        $definitionName = $this->definitionNameFactory->create($className, $format, $className, $operation, $serializerContext);
        $prefix = $this->getSchemaUriPrefix($schema->getVersion());
        $definitions = $schema->getDefinitions();
        $collectionKey = $schema->getItemsDefinitionKey();

        // Already computed
        if (!$collectionKey && isset($definitions[$definitionName])) {
            $schema['$ref'] = $prefix.$definitionName;

            return $schema;
        }

        $key = $schema->getRootDefinitionKey() ?? $collectionKey;
        $properties = $definitions[$definitionName]['properties'] ?? [];

        // Prevent reapplying
        if (isset($definitions[$key]['description'])) {
            $definitions[$definitionName]['description'] = $definitions[$key]['description'];
        }

        if (Error::class === $className && !isset($properties['errors'])) {
            $definitions[$definitionName]['properties'] = [
                'errors' => [
                    'type' => 'array',
                    'items' => [
                        'allOf' => [
                            ['$ref' => $prefix.$key],
                            ['type' => 'object', 'properties' => ['source' => ['type' => 'object'], 'status' => ['type' => 'string']]],
                        ],
                    ],
                ],
            ];

            $schema['$ref'] = $prefix.$definitionName;

            return $schema;
        }

        if (!$collectionKey) {
            $definitions[$definitionName]['properties'] = $this->buildDefinitionPropertiesSchema($definitionName, $className, $format, $type, $operation, $schema, []);
        }

        if (!$collectionKey) {
            $schema['$ref'] = $prefix.$definitionName;

            return $schema;
        }

        if (($schema['type'] ?? '') === 'array') {
            if (!isset($definitions[self::COLLECTION_BASE_SCHEMA_NAME])) {
                $definitions[self::COLLECTION_BASE_SCHEMA_NAME] = [
                    'type' => 'object',
                    'properties' => [
                        'links' => self::LINKS_PROPS,
                        'meta' => self::META_PROPS,
                        'data' => [
                            'type' => 'array',
                        ],
                    ],
                    'required' => ['data'],
                ];
            }

            unset($schema['items']);
            unset($schema['type']);

            $schema['description'] = "$definitionName collection.";
            $schema['allOf'] = [
                ['$ref' => $prefix.self::COLLECTION_BASE_SCHEMA_NAME],
                ['type' => 'object', 'properties' => ['data' => ['type' => 'array', 'items' => ['$ref' => $prefix.$definitionName]]]],
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

    private function buildDefinitionPropertiesSchema(string $key, string $className, string $format, string $type, ?Operation $operation, Schema $schema, ?array $serializerContext): array
    {
        $definitions = $schema->getDefinitions();
        $properties = $definitions[$key]['properties'] ?? [];

        $attributes = [];
        $relationships = [];
        $relatedDefinitions = [];
        foreach ($properties as $propertyName => $property) {
            if ($relation = $this->getRelationship($className, $propertyName, $serializerContext)) {
                [$isOne, $relatedClasses] = $relation;
                $refs = [];
                foreach ($relatedClasses as $relatedClassName => $hasOperations) {
                    if (false === $hasOperations) {
                        continue;
                    }

                    $operation = $this->findOperation($relatedClassName, $type, $operation, $serializerContext);
                    $inputOrOutputClass = $this->findOutputClass($relatedClassName, $type, $operation, $serializerContext);
                    $serializerContext ??= $this->getSerializerContext($operation, $type);
                    $definitionName = $this->definitionNameFactory->create($relatedClassName, $format, $inputOrOutputClass, $operation, $serializerContext);
                    $ref = $this->getSchemaUriPrefix($schema->getVersion()).$definitionName;
                    $refs[$ref] = '$ref';
                }
                $relatedDefinitions[$propertyName] = array_flip($refs);
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
                // should probably be renamed "lid" and moved to the above node
                $attributes['_id'] = $property;
                continue;
            }
            $attributes[$propertyName] = $property;
        }

        $currentRef = $this->getSchemaUriPrefix($schema->getVersion()).$key;
        $replacement = self::PROPERTY_PROPS;
        $replacement['attributes'] = ['$ref' => $currentRef];

        $included = [];
        if (\count($relationships) > 0) {
            $replacement['relationships'] = [
                'type' => 'object',
                'properties' => $relationships,
            ];
            $included = [
                'included' => [
                    'description' => 'Related resources requested via the "include" query parameter.',
                    'type' => 'array',
                    'items' => [
                        'anyOf' => array_values($relatedDefinitions),
                    ],
                    'readOnly' => true,
                    'externalDocs' => [
                        'url' => 'https://jsonapi.org/format/#fetching-includes',
                    ],
                ],
            ];
        }

        if ($required = $definitions[$key]['required'] ?? null) {
            foreach ($required as $i => $require) {
                if (isset($relationships[$require])) {
                    $replacement['relationships']['required'][] = $require;
                    unset($required[$i]);
                }
            }

            $replacement['attributes'] = [
                'allOf' => [
                    $replacement['attributes'],
                    ['type' => 'object', 'required' => $required],
                ],
            ];

            unset($definitions[$key]['required']);
        }

        return [
            'data' => [
                'type' => 'object',
                'properties' => $replacement,
                'required' => ['type', 'id'],
            ],
        ] + $included;
    }

    private function getRelationship(string $resourceClass, string $property, ?array $serializerContext): ?array
    {
        $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property, $serializerContext ?? []);
        $types = $propertyMetadata->getBuiltinTypes() ?? [];
        $isRelationship = false;
        $isOne = $isMany = false;
        $relatedClasses = [];

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
            $resourceMetadata = $this->resourceMetadataFactory->create($className);
            $operation = $resourceMetadata->getOperation();
            // @see https://github.com/api-platform/core/issues/5501
            // @see https://github.com/api-platform/core/pull/5722
            $relatedClasses[$className] = $operation->canRead();
        }

        return $isRelationship ? [$isOne, $relatedClasses] : null;
    }
}
