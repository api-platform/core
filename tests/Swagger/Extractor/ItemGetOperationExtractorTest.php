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
use ApiPlatform\Core\Swagger\Extractor\ItemGetOperationExtractor;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

class ItemGetOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);

        $collectionGetOperationExtractor = new ItemGetOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal()
        );

        $this->assertTrue($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'WRONG';
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'GET';
        $operationData['isCollection'] = true;
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));
    }

    public function testExtract()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'get',
            'operation' => ['method' => 'GET'],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'GET',
            'mimeTypes' => ['application/ld+json'],
        ];

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'get' => ['method' => 'GET'],
            ],
            [],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);
        $swaggerDefinitionProphecy->get($operationData, false)->shouldBeCalled()->willReturn('dummyDefinition');

        $collectionGetOperationExtractor = new ItemGetOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal()
        );

        $expectedResult = new \ArrayObject([
            '/dummies/{id}' => [
                'get' => new \ArrayObject([
                    'tags' => ['Dummy'],
                    'operationId' => 'getDummyItem',
                    'produces' => ['application/ld+json'],
                    'summary' => 'Retrieves a Dummy resource.',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ],
                    ],
                    'responses' => [
                        200 => [
                            'description' => 'Dummy resource response',
                            'schema' => ['$ref' => '#/definitions/dummyDefinition'],
                        ],
                        404 => ['description' => 'Resource not found'],
                    ],
                ]),
            ],
        ]);

        $this->assertEquals($expectedResult, $collectionGetOperationExtractor->extract($operationData));
    }
}
