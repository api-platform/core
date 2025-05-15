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
            ],
        ] + self::BASE_PROPS,
    ];

    private const ITEM_BASE_SCHEMA_OUTPUT = [
        'required' => ['@id', '@type'],
    ] + self::ITEM_BASE_SCHEMA;

    /**
     * @var array<string, true>
     */
    private array $transformed = [];

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

        $definitionName = $this->definitionNameFactory->create($className, $format, $inputOrOutputClass, $operation, $serializerContext);

        // JSON-LD is slightly different then JSON:API or HAL
        // All the references that are resources must also be in JSON-LD therefore combining
        // the HydraItemBaseSchema and the JSON schema is harder (unless we loop again through all relationship)
        // The less intensive path is to compute the jsonld schemas, then to combine in an allOf
        $schema = $this->schemaFactory->buildSchema($className, 'jsonld', $type, $operation, $schema, $serializerContext, $forceCollection);
        $definitions = $schema->getDefinitions();

        $prefix = $this->getSchemaUriPrefix($schema->getVersion());
        $collectionKey = $schema->getItemsDefinitionKey();
        $key = $schema->getRootDefinitionKey() ?? $collectionKey;

        if (!$collectionKey) {
            if ($this->transformed[$definitionName] ?? false) {
                return $schema;
            }

            $baseName = Schema::TYPE_OUTPUT === $type ? self::ITEM_BASE_SCHEMA_NAME : self::ITEM_BASE_SCHEMA_OUTPUT_NAME;

            if ($this->isResourceClass($inputOrOutputClass)) {
                if (!isset($definitions[$baseName])) {
                    $definitions[$baseName] = Schema::TYPE_OUTPUT === $type ? self::ITEM_BASE_SCHEMA_OUTPUT : self::ITEM_BASE_SCHEMA;
                }
            }

            $allOf = new \ArrayObject(['allOf' => [
                ['$ref' => $prefix.$baseName],
                $definitions[$key],
            ]]);

            if (isset($definitions[$key]['description'])) {
                $allOf['description'] = $definitions[$key]['description'];
            }

            $definitions[$definitionName] = $allOf;
            unset($definitions[$definitionName]['allOf'][1]['description']);

            $schema['$ref'] = $prefix.$definitionName;

            $this->transformed[$definitionName] = true;

            return $schema;
        }

        if (($schema['type'] ?? '') !== 'array') {
            return $schema;
        }

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

        $schema['type'] = 'object';
        $schema['description'] = "$definitionName collection.";
        $schema['allOf'] = [
            ['$ref' => $prefix.self::COLLECTION_BASE_SCHEMA_NAME],
            [
                'type' => 'object',
                'properties' => [
                    $hydraPrefix.'member' => [
                        'type' => 'array',
                        'items' => $schema['items'],
                    ],
                ],
            ],
        ];

        unset($schema['items']);

        return $schema;
    }

    public function setSchemaFactory(SchemaFactoryInterface $schemaFactory): void
    {
        if ($this->schemaFactory instanceof SchemaFactoryAwareInterface) {
            $this->schemaFactory->setSchemaFactory($schemaFactory);
        }
    }
}
