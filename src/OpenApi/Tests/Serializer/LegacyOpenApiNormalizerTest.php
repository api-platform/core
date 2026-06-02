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

namespace ApiPlatform\OpenApi\Tests\Serializer;

use ApiPlatform\OpenApi\Serializer\LegacyOpenApiNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class LegacyOpenApiNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testReturnsUntouchedWhenSpecVersionIsNot30(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'name' => ['type' => ['string', 'null']],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($document, $normalizer->normalize(new \stdClass(), null, []));
    }

    public function testConvertsNullableScalarUsingNullableFlag(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'name' => ['type' => ['string', 'null']],
                    ],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'name' => ['type' => 'string', 'nullable' => true],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testKeepsItemsWhenArrayTypeIsNullable(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'tags' => [
                            'type' => ['array', 'null'],
                            'items' => ['type' => 'string'],
                        ],
                    ],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'nullable' => true,
                        ],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testRecursesIntoNestedProperties(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'TestResource' => [
                    'properties' => [
                        'testEmbeddable' => [
                            'type' => ['object', 'null'],
                            'properties' => [
                                'testArrayOrNull' => [
                                    'type' => ['array', 'null'],
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'TestResource' => [
                    'properties' => [
                        'testEmbeddable' => [
                            'type' => 'object',
                            'properties' => [
                                'testArrayOrNull' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                    'nullable' => true,
                                ],
                            ],
                            'nullable' => true,
                        ],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testRecursesIntoItems(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => ['string', 'null']],
                        ],
                    ],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string', 'nullable' => true],
                        ],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testRecursesIntoAllOfOneOfAnyOf(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'a' => ['allOf' => [['type' => ['string', 'null']]]],
                        'b' => ['oneOf' => [['type' => ['integer', 'null']]]],
                        'c' => ['anyOf' => [['type' => ['boolean', 'null']]]],
                    ],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'a' => ['allOf' => [['type' => 'string', 'nullable' => true]]],
                        'b' => ['oneOf' => [['type' => 'integer', 'nullable' => true]]],
                        'c' => ['anyOf' => [['type' => 'boolean', 'nullable' => true]]],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testRecursesIntoAdditionalProperties(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => ['string', 'null']],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'string', 'nullable' => true],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testFallsBackToAnyOfForMultipleNonNullTypes(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'mixed' => ['type' => ['string', 'integer', 'null']],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);
        $result = $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']);

        $property = $result['components']['schemas']['Dummy']['properties']['mixed'];
        $this->assertArrayNotHasKey('type', $property);
        $this->assertSame([
            ['type' => 'string'],
            ['type' => 'integer'],
        ], $property['anyOf']);
        $this->assertTrue($property['nullable']);
    }

    public function testConvertsExamplesToExampleRecursively(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'name' => ['type' => 'string', 'examples' => ['Alice', 'Bob']],
                        'nested' => [
                            'type' => 'object',
                            'properties' => [
                                'inner' => ['type' => 'string', 'examples' => ['x']],
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        $expected = [
            'openapi' => '3.0.0',
            'components' => ['schemas' => [
                'Dummy' => [
                    'properties' => [
                        'name' => ['type' => 'string', 'example' => ['Alice', 'Bob']],
                        'nested' => [
                            'type' => 'object',
                            'properties' => [
                                'inner' => ['type' => 'string', 'example' => ['x']],
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        $normalizer = $this->buildNormalizer($document);

        $this->assertSame($expected, $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']));
    }

    public function testWalksPathOperationSchemas(): void
    {
        $document = [
            'openapi' => '3.1.0',
            'paths' => [
                '/dummies' => [
                    'post' => [
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => ['string', 'null']],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'components' => ['schemas' => []],
        ];

        $normalizer = $this->buildNormalizer($document);
        $result = $normalizer->normalize(new \stdClass(), null, ['spec_version' => '3.0.0']);

        $schema = $result['paths']['/dummies']['post']['requestBody']['content']['application/json']['schema'];
        $this->assertSame('object', $schema['type']);
        $this->assertSame(['type' => 'string', 'nullable' => true], $schema['properties']['name']);
    }

    private function buildNormalizer(array $document): LegacyOpenApiNormalizer
    {
        $decorated = $this->prophesize(NormalizerInterface::class);
        $decorated->normalize(\Prophecy\Argument::any(), \Prophecy\Argument::any(), \Prophecy\Argument::any())->willReturn($document);

        return new LegacyOpenApiNormalizer($decorated->reveal());
    }
}
