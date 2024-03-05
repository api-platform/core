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

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;

/**
 * Decorator factory which adds HAL properties to the JSON Schema document.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Jachim Coudenys <jachimcoudenys@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface
{
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

    public function __construct(private readonly SchemaFactoryInterface $schemaFactory)
    {
        $this->addDistinctFormat('jsonhal');
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'jsonhal', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->schemaFactory->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
        if ('jsonhal' !== $format) {
            return $schema;
        }

        $definitions = $schema->getDefinitions();
        if ($key = $schema->getRootDefinitionKey()) {
            $definitions[$key]['properties'] = self::BASE_PROPS + ($definitions[$key]['properties'] ?? []);

            return $schema;
        }
        if ($key = $schema->getItemsDefinitionKey()) {
            $definitions[$key]['properties'] = self::BASE_PROPS + ($definitions[$key]['properties'] ?? []);
        }

        if (($schema['type'] ?? '') === 'array') {
            $items = $schema['items'];
            unset($schema['items']);

            $schema['type'] = 'object';
            $schema['properties'] = [
                '_embedded' => [
                    'type' => 'array',
                    'items' => $items,
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
            ];
            $schema['required'] = [
                '_links',
                '_embedded',
            ];

            return $schema;
        }

        return $schema;
    }

    public function addDistinctFormat(string $format): void
    {
        if (method_exists($this->schemaFactory, 'addDistinctFormat')) {
            $this->schemaFactory->addDistinctFormat($format);
        }
    }
}
