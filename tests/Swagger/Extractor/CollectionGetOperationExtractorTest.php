<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Swagger\Extractor;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Swagger\Extractor\CollectionGetOperationExtractor;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Swagger\Util\SwaggerFilterDefinitions;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

class CollectionGetOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);
        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);

        $collectionGetOperationExtractor = new CollectionGetOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal(),
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $this->assertTrue($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'WRONG';
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'GET';
        $operationData['isCollection'] = false;
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));
    }

    public function testExtract()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [],
            [
                'get' => ['method' => 'GET'],
            ],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);
        $swaggerDefinitionProphecy->get($operationData, false)->shouldBeCalled()->willReturn('dummyDefinition');

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get($operationData)->shouldBeCalled()->willReturn([]);

        $collectionGetOperationExtractor = new CollectionGetOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal(),
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $expectedResult = new \ArrayObject([
            '/dummies' => [
                'get' => new \ArrayObject([
                    'tags' => [
                        'Dummy',
                    ],
                    'operationId' => 'getDummyCollection',
                    'produces' => ['application/ld+json'],
                    'summary' => 'Retrieves the collection of Dummy resources.',
                    'responses' => [
                        200 => [
                            'description' => 'Dummy collection response',
                            'schema' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/definitions/dummyDefinition'],
                            ],
                        ],
                    ],
                ]),
            ],
        ]);

        $this->assertEquals($expectedResult, $collectionGetOperationExtractor->extract($operationData));
    }

    public function testExtractWithFilters()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET', 'filters' => ['f1', 'f2']],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [],
            [
                'get' => ['method' => 'GET', 'filters' => ['f1', 'f2']],
            ],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);
        $swaggerDefinitionProphecy->get($operationData, false)->shouldBeCalled()->willReturn('dummyDefinition');

        $filters = [
            [
                'x-foo' => 'bar',
                'name' => 'name',
                'in' => 'query',
                'required' => true,
                'type' => 'string',
            ],
            [
                'name' => 'ha',
                'in' => 'query',
                'required' => false,
                'type' => 'integer',
            ],
        ];

        $swaggerFilterDefinitionsProphecy = $this->prophesize(SwaggerFilterDefinitions::class);
        $swaggerFilterDefinitionsProphecy->get($operationData)->shouldBeCalled()->willReturn($filters);

        $collectionGetOperationExtractor = new CollectionGetOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal(),
            $swaggerFilterDefinitionsProphecy->reveal()
        );

        $expectedResult = new \ArrayObject([
            '/dummies' => [
                'get' => new \ArrayObject([
                    'tags' => ['Dummy'],
                    'operationId' => 'getDummyCollection',
                    'produces' => ['application/ld+json'],
                    'summary' => 'Retrieves the collection of Dummy resources.',
                    'responses' => [
                        200 => [
                            'description' => 'Dummy collection response',
                            'schema' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/definitions/dummyDefinition'],
                            ],
                        ],
                    ],
                    'parameters' => [
                        [
                            'x-foo' => 'bar',
                            'name' => 'name',
                            'in' => 'query',
                            'required' => true,
                            'type' => 'string',
                        ],
                        [
                            'name' => 'ha',
                            'in' => 'query',
                            'required' => false,
                            'type' => 'integer',
                        ],
                    ],
                ]),
            ],
        ]);

        $this->assertEquals($expectedResult, $collectionGetOperationExtractor->extract($operationData));
    }
}
