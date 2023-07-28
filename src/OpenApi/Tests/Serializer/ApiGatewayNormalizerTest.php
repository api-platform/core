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

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Serializer\ApiGatewayNormalizer;
use ApiPlatform\OpenApi\Serializer\OpenApiNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;

final class ApiGatewayNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @group legacy
     */
    public function testSupportsNormalization(): void
    {
        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->supportsNormalization(OpenApiNormalizer::FORMAT, OpenApi::class)->willReturn(true);
        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $normalizerProphecy->willImplement(CacheableSupportsMethodInterface::class);
            $normalizerProphecy->hasCacheableSupportsMethod()->willReturn(true);
        }

        $normalizer = new ApiGatewayNormalizer($normalizerProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(OpenApiNormalizer::FORMAT, OpenApi::class));

        if (!method_exists(Serializer::class, 'getSupportedTypes')) {
            $this->assertTrue($normalizer->hasCacheableSupportsMethod());
        }
    }

    public function testNormalize(): void
    {
        $swaggerDocument = [
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'post' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy',
                                ],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy-list_details',
                                ],
                            ],
                        ],
                    ]),
                    'get' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'relatedDummy',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'relatedDummy[]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/definitions/Dummy-list',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'string',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy-list_details',
                                ],
                            ],
                        ],
                    ]),
                ],
                '/dummies/{id}/what' => [
                    'post' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy:InputDto',
                                ],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy:OutputDto',
                                ],
                            ],
                        ],
                    ]),
                ],
            ]),
            'components' => ['schemas' => [
                'Dummy' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'readOnly' => true,
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'Dummy-list' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'readOnly' => true,
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'Dummy-list_details' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'readOnly' => true,
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                        'relatedDummy' => new \ArrayObject([
                            '$ref' => '#/definitions/RelatedDummy-list_details',
                        ]),
                    ],
                ]),
                'Dummy:OutputDto' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'baz' => new \ArrayObject([
                            'readOnly' => true,
                            'type' => 'string',
                        ]),
                        'bat' => new \ArrayObject([
                            'type' => 'integer',
                        ]),
                    ],
                ]),
                'Dummy:InputDto' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'foo' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                        'bar' => new \ArrayObject([
                            'type' => 'integer',
                        ]),
                    ],
                ]),
                'RelatedDummy-list_details' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                    ],
                ]),
            ]],
        ];

        $modifiedSwaggerDocument = [
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'post' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy',
                                ],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummylistdetails',
                                ],
                            ],
                        ],
                    ]),
                    'get' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'relatedDummy',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/definitions/Dummylist',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'string',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummylistdetails',
                                ],
                            ],
                        ],
                    ]),
                ],
                '/dummies/{id}/what' => [
                    'post' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'schema' => [
                                    '$ref' => '#/definitions/DummyInputDto',
                                ],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    '$ref' => '#/definitions/DummyOutputDto',
                                ],
                            ],
                        ],
                    ]),
                ],
            ]),
            'components' => ['schemas' => [
                'Dummy' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'Dummylist' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'Dummylistdetails' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                        'relatedDummy' => new \ArrayObject([
                            '$ref' => '#/definitions/RelatedDummylistdetails',
                        ]),
                    ],
                ]),
                'DummyOutputDto' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'baz' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                        'bat' => new \ArrayObject([
                            'type' => 'integer',
                        ]),
                    ],
                ]),
                'DummyInputDto' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'foo' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                        'bar' => new \ArrayObject([
                            'type' => 'integer',
                        ]),
                    ],
                ]),
                'RelatedDummylistdetails' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                    ],
                ]),
            ]],
            'basePath' => '/',
        ];

        $documentation = $this->getOpenApi();
        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize($documentation, OpenApiNormalizer::FORMAT, [
            ApiGatewayNormalizer::API_GATEWAY => true,
        ])->willReturn($swaggerDocument);

        $normalizer = new ApiGatewayNormalizer($normalizerProphecy->reveal());

        $this->assertEquals($modifiedSwaggerDocument, $normalizer->normalize($documentation, OpenApiNormalizer::FORMAT, [
            ApiGatewayNormalizer::API_GATEWAY => true,
        ]));
    }

    public function testNormalizeNotInApiGatewayContext(): void
    {
        $documentation = $this->getOpenApi();

        $swaggerDocument = [
            'paths' => new \ArrayObject([
                '/dummies' => [
                    'post' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy',
                                ],
                            ],
                        ],
                        'responses' => [
                            201 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy-list_details',
                                ],
                            ],
                        ],
                    ]),
                    'get' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'relatedDummy',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                            [
                                'name' => 'relatedDummy[]',
                                'in' => 'query',
                                'required' => false,
                                'type' => 'string',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/definitions/Dummy-list',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ],
                '/dummies/{id}' => [
                    'get' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'type' => 'string',
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy-list_details',
                                ],
                            ],
                        ],
                    ]),
                ],
                '/dummies/{id}/what' => [
                    'post' => new \ArrayObject([
                        'parameters' => [
                            [
                                'name' => 'dummy',
                                'in' => 'body',
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy:InputDto',
                                ],
                            ],
                        ],
                        'responses' => [
                            200 => [
                                'schema' => [
                                    '$ref' => '#/definitions/Dummy:OutputDto',
                                ],
                            ],
                        ],
                    ]),
                ],
            ]),
            'components' => ['schemas' => new \ArrayObject([
                'Dummy' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'readOnly' => true,
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'Dummy-list' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'readOnly' => true,
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                    ],
                ]),
                'Dummy-list_details' => new \ArrayObject([
                    'properties' => [
                        'id' => [
                            'readOnly' => true,
                            'type' => 'integer',
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                        'relatedDummy' => new \ArrayObject([
                            '$ref' => '#/definitions/RelatedDummy-list_details',
                        ]),
                    ],
                ]),
                'Dummy:OutputDto' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'baz' => new \ArrayObject([
                            'readOnly' => true,
                            'type' => 'string',
                        ]),
                        'bat' => new \ArrayObject([
                            'type' => 'integer',
                        ]),
                    ],
                ]),
                'Dummy:InputDto' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'foo' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                        'bar' => new \ArrayObject([
                            'type' => 'integer',
                        ]),
                    ],
                ]),
                'RelatedDummy-list_details' => new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'name' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                    ],
                ]),
            ])],
        ];

        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $normalizerProphecy->normalize($documentation, OpenApiNormalizer::FORMAT, Argument::type('array'))->willReturn($swaggerDocument);

        $normalizer = new ApiGatewayNormalizer($normalizerProphecy->reveal());

        $this->assertEquals($swaggerDocument, $normalizer->normalize($documentation, OpenApiNormalizer::FORMAT));
    }

    private function getOpenApi(): OpenApi
    {
        return new OpenApi(new Info('test', '0'), [], new Paths());
    }
}
