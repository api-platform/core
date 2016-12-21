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
use ApiPlatform\Core\Swagger\Extractor\CollectionPostOperationExtractor;
use ApiPlatform\Core\Swagger\Util\SwaggerDefinitions;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;

class CollectionPostOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'post',
            'operation' => ['method' => 'POST'],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'POST',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);

        $collectionGetOperationExtractor = new CollectionPostOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal()
        );

        $this->assertTrue($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'WRONG';
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'POST';
        $operationData['isCollection'] = false;
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));
    }

    public function testExtract()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'post',
            'operation' => ['method' => 'POST'],
            'isCollection' => true,
            'path' => '/dummies',
            'method' => 'POST',
            'mimeTypes' => ['application/ld+json'],
        ];

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [],
            [
                'post' => ['method' => 'POST'],
            ],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $swaggerDefinitionProphecy = $this->prophesize(SwaggerDefinitions::class);
        $swaggerDefinitionProphecy->get($operationData, true)->shouldBeCalled()->willReturn('dummyDefinition');
        $swaggerDefinitionProphecy->get($operationData, false)->shouldBeCalled()->willReturn('dummyDefinition');

        $collectionGetOperationExtractor = new CollectionPostOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $swaggerDefinitionProphecy->reveal()
        );

        $expectedResult = new \ArrayObject([
            '/dummies' => [
                'post' => new \ArrayObject([
                    'tags' => ['Dummy'],
                    'operationId' => 'postDummyCollection',
                    'consumes' => ['application/ld+json'],
                    'produces' => ['application/ld+json'],
                    'summary' => 'Creates a Dummy resource.',
                    'parameters' => [[
                        'name' => 'dummy',
                        'in' => 'body',
                        'description' => 'The new Dummy resource',
                        'schema' => ['$ref' => '#/definitions/dummyDefinition'],
                    ]],
                    'responses' => [
                        201 => [
                            'description' => 'Dummy resource created',
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
