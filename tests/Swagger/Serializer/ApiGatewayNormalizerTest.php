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

namespace ApiPlatform\Core\Tests\Swagger\Serializer;

use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ApiGatewayNormalizerTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $documentationNormalizerProphecy;

    /**
     * @var ObjectProphecy
     */
    private $objectProphecy;

    /**
     * @var ApiGatewayNormalizer
     */
    private $normalizer;

    protected function setUp()
    {
        $this->documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $this->documentationNormalizerProphecy->willImplement(CacheableSupportsMethodInterface::class);
        $this->documentationNormalizerProphecy->hasCacheableSupportsMethod()->willReturn(true);
        $this->objectProphecy = $this->prophesize(\stdClass::class);
        $this->normalizer = new ApiGatewayNormalizer($this->documentationNormalizerProphecy->reveal());
    }

    public function testSupportsNormalization()
    {
        $this->documentationNormalizerProphecy->supportsNormalization('foo', 'bar')->willReturn(true)->shouldBeCalledTimes(1);
        $this->assertTrue($this->normalizer->supportsNormalization('foo', 'bar'));
        $this->assertTrue($this->normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeWithoutApiGateway()
    {
        $this->documentationNormalizerProphecy->normalize($this->objectProphecy, 'jsonld', [])
            ->willReturn(['basePath' => '/api'])
            ->shouldBeCalledTimes(1);
        $this->assertEquals(['basePath' => '/api'], $this->normalizer->normalize($this->objectProphecy->reveal(), 'jsonld'));
    }

    public function testNormalizeWithApiGateway()
    {
        $this->documentationNormalizerProphecy->normalize($this->objectProphecy, 'jsonld', ['api_gateway' => true])
            ->willReturn([
                'basePath' => '',
                'paths' => [
                    '/foo' => [
                        'get' => [
                            'responses' => [
                                '200' => [
                                    'schema' => [
                                        'items' => [
                                            '$ref' => '#/definitions/Foo-foo_read',
                                        ],
                                    ],
                                ],
                            ],
                            'parameters' => [
                                [
                                    'name' => 'bar',
                                ],
                                [
                                    'name' => 'bar[]',
                                ],
                            ],
                        ],
                        'post' => [
                            'parameters' => [
                                [
                                    'name' => 'foo',
                                    'schema' => [
                                        '$ref' => '#/definitions/Foo-foo_write',
                                    ],
                                ],
                            ],
                            'responses' => [
                                '201' => [
                                    'schema' => [
                                        'items' => [
                                            '$ref' => '#/definitions/Foo-foo_read',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'definitions' => new \ArrayObject([
                    'Foo-foo_write' => [
                        'properties' => [
                            'bar' => [
                                '$ref' => '#/definitions/Bar-bar_read',
                            ],
                        ],
                    ],
                    'Foo-foo_read' => [
                        'properties' => [
                            'id' => [
                                'readOnly' => true,
                                'type' => 'integer',
                            ],
                            'bar' => [
                                '$ref' => '#/definitions/Bar-bar_write',
                            ],
                        ],
                    ],
                    'Bar-bar_write' => [
                        'properties' => [
                            'foo' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'Bar-bar_read' => [
                        'properties' => [
                            'id' => [
                                'readOnly' => true,
                                'type' => 'integer',
                            ],
                            'foo' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ]),
            ])
            ->shouldBeCalledTimes(1);
        $this->assertEquals([
            'basePath' => '/',
            'paths' => [
                '/foo' => [
                    'get' => [
                        'responses' => [
                            '200' => [
                                'schema' => [
                                    'items' => [
                                        '$ref' => '#/definitions/Foofooread',
                                    ],
                                ],
                            ],
                        ],
                        'parameters' => [
                            [
                                'name' => 'bar',
                            ],
                        ],
                    ],
                    'post' => [
                        'parameters' => [
                            [
                                'name' => 'foo',
                                'schema' => [
                                    '$ref' => '#/definitions/Foofoowrite',
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'schema' => [
                                    'items' => [
                                        '$ref' => '#/definitions/Foofooread',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'definitions' => new \ArrayObject([
                'Foofoowrite' => [
                    'properties' => [
                        'bar' => [
                            '$ref' => '#/definitions/Barbarread',
                        ],
                    ],
                ],
                'Foofooread' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'bar' => [
                            '$ref' => '#/definitions/Barbarwrite',
                        ],
                    ],
                ],
                'Barbarwrite' => [
                    'properties' => [
                        'foo' => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'Barbarread' => [
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'foo' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ]),
        ], $this->normalizer->normalize($this->objectProphecy->reveal(), 'jsonld', ['api_gateway' => true]));
    }
}
