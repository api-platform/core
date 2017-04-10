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
use ApiPlatform\Core\Swagger\Extractor\ItemDeleteOperationExtractor;
use Symfony\Component\Serializer\Tests\Fixtures\Dummy;

class ItemDeleteOperationExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function testSupports()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'delete',
            'operation' => ['method' => 'DELETE'],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'DELETE',
            'mimeTypes' => ['application/ld+json'],
        ];

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);

        $collectionGetOperationExtractor = new ItemDeleteOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal()
        );

        $this->assertTrue($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'WRONG';
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));

        $operationData['method'] = 'DELETE';
        $operationData['isCollection'] = true;
        $this->assertFalse($collectionGetOperationExtractor->supportsExtraction($operationData));
    }

    public function testExtract()
    {
        $operationData = [
            'resourceClass' => Dummy::class,
            'operationName' => 'delete',
            'operation' => ['method' => 'DELETE'],
            'isCollection' => false,
            'path' => '/dummies/{id}',
            'method' => 'DELETE',
            'mimeTypes' => ['application/ld+json'],
        ];

        $dummyMetadata = new ResourceMetadata(
            'Dummy',
            'This is a dummy.',
            'http://schema.example.com/Dummy',
            [
                'delete' => ['method' => 'DELETE'],
            ],
            [],
            []
        );

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn($dummyMetadata);

        $collectionGetOperationExtractor = new ItemDeleteOperationExtractor(
            $resourceMetadataFactoryProphecy->reveal()
        );

        $expectedResult = new \ArrayObject([
            '/dummies/{id}' => [
                'delete' => new \ArrayObject([
                    'tags' => ['Dummy'],
                    'operationId' => 'deleteDummyItem',
                    'summary' => 'Removes the Dummy resource.',
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'type' => 'integer',
                            'required' => true,
                        ],
                    ],
                    'responses' => [
                        '204' => ['description' => 'Dummy resource deleted'],
                        '404' => ['description' => 'Resource not found'],
                    ],
                ]),
            ],
        ]);

        $this->assertEquals($expectedResult, $collectionGetOperationExtractor->extract($operationData));
    }
}
