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
use ApiPlatform\Core\Swagger\Extractor\ItemPutOperationExtractor;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

class ItemPutOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'put',
            'operation' => ['method' => 'PUT'],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'PUT',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);

        $collectionGetOperationExtractor = new ItemPutOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal()
        );

        $this->assertTrue($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'WRONG';
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'PUT';
        $operationData['isCollection'] = true;
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));
    }

    public function testExtract()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'put',
            'operation' => ['method' => 'PUT'],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'PUT',
            'mimeTypes' => ['application/ld+json'],
        ];

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'put' => ['method' => 'PUT'],
            ],
            [],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);
        $swaggerDefinitionProphecy->get($operationData, true)->shouldBeCalled()->willReturn('dummyDefinition');
        $swaggerDefinitionProphecy->get($operationData, false)->shouldBeCalled()->willReturn('dummyDefinition');

        $collectionGetOperationExtractor = new ItemPutOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal()
        );

        $expectedResult = new \ArrayObject([
            '/dummies/{id}' => [
                'put' => new \ArrayObject([
                    'tags' => ['Dummy'],
                    'operationId' => 'putDummyItem',
                    'consumes' => ['application/ld+json'],
                    'produces' => ['application/ld+json'],
                    'summary' => 'Replaces the Dummy resource.',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ],
                        [
                            'name' => 'dummy',
                            'in' => 'body',
                            'description' => 'The updated Dummy resource',
                            'schema' => ['$ref' => '#/definitions/dummyDefinition'],
                        ],
                    ],
                    'responses' => [
                        200 => [
                            'description' => 'Dummy resource updated',
                            'schema' => ['$ref' => '#/definitions/dummyDefinition'],
                        ],
                        400 => ['description' => 'Invalid input'],
                        404 => ['description' => 'Resource not found'],
                    ],
                ]),
            ],
        ]);

        $this->assertEquals($expectedResult, $collectionGetOperationExtractor->extract($operationData));
    }
}
