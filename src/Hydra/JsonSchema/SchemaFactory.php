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

namespace ApiPlatform\Hydra\JsonSchema;

use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\JsonLd\Serializer\HydraPrefixTrait;
use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\JsonSchema\ResourceMetadataTrait;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\SchemaUriPrefixTrait;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

/**
 * Decorator factory which adds Hydra properties to the JSON Schema document.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    use HydraPrefixTrait;
    use ResourceMetadataTrait;
    use SchemaUriPrefixTrait;

    private const ITEM_BASE_SCHEMA_NAME = 'HydraItemBaseSchema';
    private const ITEM_BASE_SCHEMA_OUTPUT_NAME = 'HydraOutputBaseSchema';
    private const COLLECTION_BASE_SCHEMA_NAME = 'HydraCollectionBaseSchema';
    private const BASE_PROP = [
        'type' => 'string',
    ];
    private const BASE_PROPS = [
        '@id' => self::BASE_PROP,
        '@type' => self::BASE_PROP,
    ];
    private const ITEM_BASE_SCHEMA = [
        'type' => 'object',
        'properties' => [
            '@context' => [
                'oneOf' => [
                    ['type' => 'string'],
                    [
                        'type' => 'object',
                        'properties' => [
                            '@vocab' => [
                                'type' => 'string',
                            ],
                            'hydra' => [
                                'type' => 'string',
                                'enum' => [ContextBuilder::HYDRA_NS],
                            ],
                        ],
                        'required' => ['@vocab', 'hydra'],
                        'additionalProperties' => true,
                    ],
                ],
            ] + self::BASE_PROPS,
        ],
    ];

    private const ITEM_BASE_SCHEMA_OUTPUT = [
        'required' => ['@id', '@type'],
    ] + self::ITEM_BASE_SCHEMA;

    /**
     * @param array<string, mixed> $defaultContext
     */
    public function __construct(
        private readonly SchemaFactoryInterface $schemaFactory,
        private readonly array $defaultContext = [],
        private ?DefinitionNameFactoryInterface $definitionNameFactory = null,
        ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null,
    ) {
        if (!$definitionNameFactory) {
            $this->definitionNameFactory = new DefinitionNameFactory();
        }
        $this->resourceMetadataFactory = $resourceMetadataFactory;

        if ($this->schemaFactory instanceof SchemaFactoryAwareInterface) {
            $this->schemaFactory->setSchemaFactory($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'jsonld', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        if ('jsonld' !== $format || 'input' === $type) {
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

        if ($schema) {
            $definitions = $schema->getDefinitions();
            $jsonDefinitionName = $this->definitionNameFactory->create($className, 'json', $className, $operation, $serializerContext);

            if (!isset($definitions[$jsonDefinitionName])) {
                $schema = $this->schemaFactory->buildSchema($className, 'json', $type, $operation, $schema, $serializerContext, $forceCollection);
            }
        } else {
            $schema = $this->schemaFactory->buildSchema($className, 'json', $type, $operation, $schema, $serializerContext, $forceCollection);
        }

        $definitionName = $this->definitionNameFactory->create($className, $format, $className, $operation, $serializerContext);
        $definitions = $schema->getDefinitions();

        $addJsonLdBaseSchema = false;

        if (!isset($definitions[$definitionName])) {
            $addJsonLdBaseSchema = true;
            // only compute json-ld references, skip the scalar properties as they're inherited from the json format
            $schema = $this->schemaFactory->buildSchema($className, 'jsonld', $type, $operation, $schema, [self::COMPUTE_REFERENCES => true] + $serializerContext, $forceCollection);
        }

        $prefix = $this->getSchemaUriPrefix($schema->getVersion());
        $collectionKey = $schema->getItemsDefinitionKey();

        $key = $schema->getRootDefinitionKey() ?? $collectionKey;
        $name = Schema::TYPE_OUTPUT === $type ? self::ITEM_BASE_SCHEMA_NAME : self::ITEM_BASE_SCHEMA_OUTPUT_NAME;
        if (!isset($definitions[$name])) {
            $definitions[$name] = Schema::TYPE_OUTPUT === $type ? self::ITEM_BASE_SCHEMA_OUTPUT : self::ITEM_BASE_SCHEMA;
        }

        if (!$collectionKey && isset($definitions[$definitionName])) {
            if (!$addJsonLdBaseSchema) {
                $schema['$ref'] = $prefix.$definitionName;

                return $schema;
            }

            $allOf = [
                ['$ref' => $prefix.$name],
                ['$ref' => $prefix.$key],
            ];

            // if there're no properties, we did not compute any json-ld specific reference
            if (isset($definitions[$definitionName]['properties'])) {
                $allOf[] = $definitions[$definitionName];
            }

            $definitions[$definitionName] = new \ArrayObject([
                'allOf' => $allOf,
            ]);

            $schema->setDefinitions($definitions);
            $schema['$ref'] = $prefix.$definitionName;

            return $schema;
        }

        if (isset($definitions[$key]['description'])) {
            $definitions[$definitionName]['description'] = $definitions[$key]['description'];
        }

        // handle hydra:Collection
        if (($schema['type'] ?? '') === 'array') {
            $hydraPrefix = $this->getHydraPrefix($serializerContext + $this->defaultContext);

            if (!isset($definitions[self::COLLECTION_BASE_SCHEMA_NAME])) {
                switch ($schema->getVersion()) {
                    // JSON Schema + OpenAPI 3.1
                    case Schema::VERSION_OPENAPI:
                    case Schema::VERSION_JSON_SCHEMA:
                        $nullableStringDefinition = ['type' => ['string', 'null']];
                        break;
                        // Swagger
                    default:
                        $nullableStringDefinition = ['type' => 'string'];
                        break;
                }

                $definitions[self::COLLECTION_BASE_SCHEMA_NAME] = [
                    'type' => 'object',
                    'required' => [
                        $hydraPrefix.'member',
                    ],
                    'properties' => [
                        $hydraPrefix.'member' => [
                            'type' => 'array',
                        ],
                        $hydraPrefix.'totalItems' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        $hydraPrefix.'view' => [
                            'type' => 'object',
                            'properties' => [
                                '@id' => [
                                    'type' => 'string',
                                    'format' => 'iri-reference',
                                ],
                                '@type' => [
                                    'type' => 'string',
                                ],
                                $hydraPrefix.'first' => [
                                    'type' => 'string',
                                    'format' => 'iri-reference',
                                ],
                                $hydraPrefix.'last' => [
                                    'type' => 'string',
                                    'format' => 'iri-reference',
                                ],
                                $hydraPrefix.'previous' => [
                                    'type' => 'string',
                                    'format' => 'iri-reference',
                                ],
                                $hydraPrefix.'next' => [
                                    'type' => 'string',
                                    'format' => 'iri-reference',
                                ],
                            ],
                            'example' => [
                                '@id' => 'string',
                                'type' => 'string',
                                $hydraPrefix.'first' => 'string',
                                $hydraPrefix.'last' => 'string',
                                $hydraPrefix.'previous' => 'string',
                                $hydraPrefix.'next' => 'string',
                            ],
                        ],
                        $hydraPrefix.'search' => [
                            'type' => 'object',
                            'properties' => [
                                '@type' => ['type' => 'string'],
                                $hydraPrefix.'template' => ['type' => 'string'],
                                $hydraPrefix.'variableRepresentation' => ['type' => 'string'],
                                $hydraPrefix.'mapping' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            '@type' => ['type' => 'string'],
                                            'variable' => ['type' => 'string'],
                                            'property' => $nullableStringDefinition,
                                            'required' => ['type' => 'boolean'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            }

            unset($schema['items']);

            $schema['type'] = 'object';
            $schema['description'] = "$definitionName collection.";
            $schema['allOf'] = [
                ['$ref' => $prefix.self::COLLECTION_BASE_SCHEMA_NAME],
                [
                    'type' => 'object',
                    'properties' => [
                        $hydraPrefix.'member' => [
                            'type' => 'array',
                            'items' => ['$ref' => $prefix.$definitionName],
                        ],
                    ],
                ],
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

    private function collectRefs(array|\ArrayObject $baseFormatSchema, $prefix)
    {
        if (!$key = $this->getSubSchemaKey($baseFormatSchema)) {
            return null;
        }

        foreach ($baseFormatSchema[$key] as $k => $s) {
            if (isset($s['$ref'])) {
                dd($s['$ref'], $prefix);
            }

            if (!$s instanceof \ArrayObject) {
                continue;
            }

            $this->collectRefs($s, $prefix);
        }

        return [];
    }

    private function getSubSchemaKey(array|\ArrayObject $subSchema): ?string
    {
        foreach (['properties', 'items', 'allOf', 'anyOf', 'oneOf'] as $key) {
            if (isset($subSchema[$key])) {
                return $key;
            }
        }

        return null;
    }
}
