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

namespace ApiPlatform\Core\Hydra\JsonSchema;

use ApiPlatform\Core\JsonSchema\Schema;
use ApiPlatform\Core\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Core\JsonSchema\SchemaFactoryInterface;

/**
 * Decorator factory which adds Hydra properties to the JSON Schema document.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface
{
    private const BASE_PROP = [
        'readOnly' => true,
        'type' => 'string',
    ];
    private const BASE_PROPS = [
        '@context' => self::BASE_PROP,
        '@id' => self::BASE_PROP,
        '@type' => self::BASE_PROP,
    ];

    private $schemaFactory;

    public function __construct(BaseSchemaFactory $schemaFactory)
    {
        $this->schemaFactory = $schemaFactory;
        $schemaFactory->addDistinctFormat('jsonld');
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'jsonld', string $type = Schema::TYPE_OUTPUT, ?string $operationType = null, ?string $operationName = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->schemaFactory->buildSchema($className, $format, $type, $operationType, $operationName, $schema, $serializerContext, $forceCollection);
        if ('jsonld' !== $format) {
            return $schema;
        }

        $definitions = $schema->getDefinitions();
        if ($key = $schema->getRootDefinitionKey()) {
            $definitions[$key]['properties'] = self::BASE_PROPS + ($definitions[$key]['properties'] ?? []);

            return $schema;
        }

        if (($schema['type'] ?? '') === 'array') {
            // hydra:collection
            $items = $schema['items'];
            unset($schema['items']);

            $schema['type'] = 'object';
            $schema['properties'] = [
                'hydra:member' => [
                    'type' => 'array',
                    'items' => $items,
                ],
                'hydra:totalItems' => [
                    'type' => 'integer',
                    'minimum' => 0,
                ],
                'hydra:view' => [
                    'type' => 'object',
                    'properties' => [
                        '@id' => [
                            'type' => 'string',
                            'format' => 'iri-reference',
                        ],
                        '@type' => [
                            'type' => 'string',
                        ],
                        'hydra:first' => [
                            'type' => 'string',
                            'format' => 'iri-reference',
                        ],
                        'hydra:last' => [
                            'type' => 'string',
                            'format' => 'iri-reference',
                        ],
                        'hydra:next' => [
                            'type' => 'string',
                            'format' => 'iri-reference',
                        ],
                    ],
                ],
                'hydra:search' => [
                    'type' => 'object',
                    'properties' => [
                        '@type' => ['type' => 'string'],
                        'hydra:template' => ['type' => 'string'],
                        'hydra:variableRepresentation' => ['type' => 'string'],
                        'hydra:mapping' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    '@type' => ['type' => 'string'],
                                    'variable' => ['type' => 'string'],
                                    'property' => ['type' => 'string'],
                                    'required' => ['type' => 'boolean'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $schema['required'] = [
                'hydra:member',
            ];

            return $schema;
        }

        return $schema;
    }
}
