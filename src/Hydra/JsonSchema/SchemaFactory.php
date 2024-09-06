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
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryAwareInterface;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation;

/**
 * Decorator factory which adds Hydra properties to the JSON Schema document.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SchemaFactory implements SchemaFactoryInterface, SchemaFactoryAwareInterface
{
    use HydraPrefixTrait;
    private const BASE_PROP = [
        'readOnly' => true,
        'type' => 'string',
    ];
    private const BASE_PROPS = [
        '@id' => self::BASE_PROP,
        '@type' => self::BASE_PROP,
    ];
    private const BASE_ROOT_PROPS = [
        '@context' => [
            'readOnly' => true,
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
    ] + self::BASE_PROPS;

    public function __construct(private readonly SchemaFactoryInterface $schemaFactory, private readonly array $defaultContext = [])
    {
        if ($this->schemaFactory instanceof SchemaFactoryAwareInterface) {
            $this->schemaFactory->setSchemaFactory($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(string $className, string $format = 'jsonld', string $type = Schema::TYPE_OUTPUT, ?Operation $operation = null, ?Schema $schema = null, ?array $serializerContext = null, bool $forceCollection = false): Schema
    {
        $schema = $this->schemaFactory->buildSchema($className, $format, $type, $operation, $schema, $serializerContext, $forceCollection);
        if ('jsonld' !== $format) {
            return $schema;
        }

        if ('input' === $type) {
            return $schema;
        }

        $definitions = $schema->getDefinitions();
        if ($key = $schema->getRootDefinitionKey()) {
            $definitions[$key]['properties'] = self::BASE_ROOT_PROPS + ($definitions[$key]['properties'] ?? []);

            return $schema;
        }
        if ($key = $schema->getItemsDefinitionKey()) {
            $definitions[$key]['properties'] = self::BASE_PROPS + ($definitions[$key]['properties'] ?? []);
        }

        if (($schema['type'] ?? '') === 'array') {
            // hydra:collection
            $items = $schema['items'];
            unset($schema['items']);

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

            $hydraPrefix = $this->getHydraPrefix(($serializerContext ?? []) + $this->defaultContext);
            $schema['type'] = 'object';
            $schema['properties'] = [
                $hydraPrefix.'member' => [
                    'type' => 'array',
                    'items' => $items,
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
            ];
            $schema['required'] = [
                $hydraPrefix.'member',
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
