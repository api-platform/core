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

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Swagger\Serializer\ApiGatewayNormalizer;
use ApiPlatform\Core\Swagger\Serializer\DocumentationNormalizer;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ApiGatewayNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsNormalization(): void
    {
        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->willImplement(CacheableSupportsMethodInterface::class);
        $documentationNormalizerProphecy->supportsNormalization(DocumentationNormalizer::FORMAT, Documentation::class)->willReturn(true);
        $documentationNormalizerProphecy->hasCacheableSupportsMethod()->willReturn(true);

        $normalizer = new ApiGatewayNormalizer($documentationNormalizerProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(DocumentationNormalizer::FORMAT, Documentation::class));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalize(): void
    {
        $documentation = new Documentation(new ResourceNameCollection([
            Dummy::class,
        ]));

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
            'definitions' => new \ArrayObject([
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
            ]),
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
            'definitions' => new \ArrayObject([
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
            ]),
            'basePath' => '/',
        ];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize($documentation, DocumentationNormalizer::FORMAT, [
            'spec_version' => 2,
            ApiGatewayNormalizer::API_GATEWAY => true,
        ])->willReturn($swaggerDocument);

        $normalizer = new ApiGatewayNormalizer($documentationNormalizerProphecy->reveal());

        $this->assertEquals($modifiedSwaggerDocument, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, [
            'spec_version' => 2,
            ApiGatewayNormalizer::API_GATEWAY => true,
        ]));
    }

    public function testNormalizeNotInApiGatewayContext(): void
    {
        $documentation = new Documentation(new ResourceNameCollection([
            Dummy::class,
        ]));

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
            'definitions' => new \ArrayObject([
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
            ]),
        ];

        $documentationNormalizerProphecy = $this->prophesize(NormalizerInterface::class);
        $documentationNormalizerProphecy->normalize($documentation, DocumentationNormalizer::FORMAT, [
            'spec_version' => 2,
        ])->willReturn($swaggerDocument);

        $normalizer = new ApiGatewayNormalizer($documentationNormalizerProphecy->reveal());

        $this->assertEquals($swaggerDocument, $normalizer->normalize($documentation, DocumentationNormalizer::FORMAT, [
            'spec_version' => 2,
        ]));
    }
}
