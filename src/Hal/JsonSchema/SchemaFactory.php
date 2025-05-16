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

namespace ApiPlatform\Hal\JsonSchema;

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
 * Decorator factory which adds HAL properties to the JSON Schema document.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Jachim Coudenys <jachimcoudenys@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    use ResourceMetadataTrait;
    use SchemaUriPrefixTrait;

    private const COLLECTION_BASE_SCHEMA_NAME = 'HalCollectionBaseSchema';

    private const HREF_PROP = [
        'href' => [
            'type' => 'string',
            'format' => 'iri-reference',
        ],
    ];
    private const BASE_PROPS = [
        '_links' => [
            'type' => 'object',
            'properties' => [
                'self' => [
                    'type' => 'object',
                    'properties' => self::HREF_PROP,
                ],
            ],
        ],
    ];

    public function __construct(private readonly SchemaFactoryInterface $schemaFactory, private ?DefinitionNameFactoryInterface $definitionNameFactory = null, ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null)
    {
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
    public function buildSchema(string $className, string $format = 'jsonhal', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        if ('jsonhal' !== $format) {
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

        $schema = $this->schemaFactory->buildSchema($className, 'json', $type, $operation, $schema, $serializerContext, $forceCollection);
        $definitions = $schema->getDefinitions();
        $definitionName = $this->definitionNameFactory->create($className, $format, $className, $operation, $serializerContext);
        $prefix = $this->getSchemaUriPrefix($schema->getVersion());
        $collectionKey = $schema->getItemsDefinitionKey();

        // Already computed
        if (!$collectionKey && isset($definitions[$definitionName])) {
            $schema['$ref'] = $prefix.$definitionName;

            return $schema;
        }

        $key = $schema->getRootDefinitionKey() ?? $collectionKey;

        $definitions[$definitionName] = [
            'allOf' => [
                ['type' => 'object', 'properties' => self::BASE_PROPS],
                ['$ref' => $prefix.$key],
            ],
        ];

        if (isset($definitions[$key]['description'])) {
            $definitions[$definitionName]['description'] = $definitions[$key]['description'];
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
                        '_embedded' => [
                            'anyOf' => [
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'item' => [
                                            'type' => 'array',
                                        ],
                                    ],
                                ],
                                ['type' => 'object'],
                            ],
                        ],
                        'totalItems' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        'itemsPerPage' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        '_links' => [
                            'type' => 'object',
                            'properties' => [
                                'self' => [
                                    'type' => 'object',
                                    'properties' => self::HREF_PROP,
                                ],
                                'first' => [
                                    'type' => 'object',
                                    'properties' => self::HREF_PROP,
                                ],
                                'last' => [
                                    'type' => 'object',
                                    'properties' => self::HREF_PROP,
                                ],
                                'next' => [
                                    'type' => 'object',
                                    'properties' => self::HREF_PROP,
                                ],
                                'previous' => [
                                    'type' => 'object',
                                    'properties' => self::HREF_PROP,
                                ],
                            ],
                        ],
                    ],
                    'required' => ['_links', '_embedded'],
                ];
            }

            unset($schema['items']);
            unset($schema['type']);

            $schema['description'] = "$definitionName collection.";
            $schema['allOf'] = [
                ['$ref' => $prefix.self::COLLECTION_BASE_SCHEMA_NAME],
                [
                    'type' => 'object',
                    'properties' => [
                        '_embedded' => [
                            'additionalProperties' => [
                                'type' => 'array',
                                'items' => ['$ref' => $prefix.$definitionName],
                            ],
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
}
