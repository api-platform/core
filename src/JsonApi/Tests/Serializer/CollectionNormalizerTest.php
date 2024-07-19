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

namespace ApiPlatform\JsonApi\Tests\Serializer;

use ApiPlatform\JsonApi\Serializer\CollectionNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class CollectionNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsNormalize(): void
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT, ['resource_class' => 'Foo']));
        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT, ['resource_class' => 'Foo', 'api_sub_level' => true]));
        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT, []));
        $this->assertTrue($normalizer->supportsNormalization(new \ArrayObject(), CollectionNormalizer::FORMAT, ['resource_class' => 'Foo']));
        $this->assertFalse($normalizer->supportsNormalization([], 'xml', ['resource_class' => 'Foo']));
        $this->assertFalse($normalizer->supportsNormalization(new \ArrayObject(), 'xml', ['resource_class' => 'Foo']));
        $this->assertEmpty($normalizer->getSupportedTypes('json'));
        $this->assertSame([
            'native-array' => true,
            '\Traversable' => true,
        ], $normalizer->getSupportedTypes($normalizer::FORMAT));
    }

    public function testNormalizePaginator(): void
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3.);
        $paginatorProphecy->getLastPage()->willReturn(7.);
        $paginatorProphecy->getItemsPerPage()->willReturn(12.);
        $paginatorProphecy->getTotalItems()->willReturn(1312.);
        $paginatorProphecy->rewind()->will(function (): void {});
        $paginatorProphecy->next()->will(function (): void {});
        $paginatorProphecy->current()->willReturn('foo');
        $paginatorProphecy->valid()->willReturn(true, false);

        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, 'Foo')->willReturn('Foo');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())
                ->withShortName('Foo')
                ->withOperations(new Operations(['get' => (new GetCollection())])),
        ]));

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos?page=3',
            'uri' => 'http://example.com/foos?page=3',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
            'root_operation_name' => 'get',
        ])->willReturn([
            'data' => [
                'type' => 'Foo',
                'id' => 1,
                'attributes' => [
                    'id' => 1,
                    'name' => 'Kévin',
                ],
            ],
        ]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $expected = [
            'links' => [
                'self' => '/foos?page=3',
                'first' => '/foos?page=1',
                'last' => '/foos?page=7',
                'prev' => '/foos?page=2',
                'next' => '/foos?page=4',
            ],
            'data' => [
                [
                    'type' => 'Foo',
                    'id' => 1,
                    'attributes' => [
                        'id' => 1,
                        'name' => 'Kévin',
                    ],
                ],
            ],
            'meta' => [
                'totalItems' => 1312,
                'itemsPerPage' => 12,
                'currentPage' => 3,
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($paginator, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos?page=3',
            'operation_name' => 'get',
            'uri' => 'http://example.com/foos?page=3',
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizePartialPaginator(): void
    {
        $paginatorProphecy = $this->prophesize(PartialPaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3.);
        $paginatorProphecy->getItemsPerPage()->willReturn(12.);
        $paginatorProphecy->rewind()->will(function (): void {});
        $paginatorProphecy->next()->will(function (): void {});
        $paginatorProphecy->current()->willReturn('foo');
        $paginatorProphecy->valid()->willReturn(true, false);
        $paginatorProphecy->count()->willReturn(1312);

        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, 'Foo')->willReturn('Foo');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())
                ->withShortName('Foo')
                ->withOperations(new Operations(['get' => (new GetCollection())])),
        ]));

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos?page=3',
            'uri' => 'http://example.com/foos?page=3',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
            'root_operation_name' => 'get',
        ])->willReturn([
            'data' => [
                'type' => 'Foo',
                'id' => 1,
                'attributes' => [
                    'id' => 1,
                    'name' => 'Kévin',
                ],
            ],
        ]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $expected = [
            'links' => [
                'self' => '/foos?page=3',
                'prev' => '/foos?page=2',
                'next' => '/foos?page=4',
            ],
            'data' => [
                [
                    'type' => 'Foo',
                    'id' => 1,
                    'attributes' => [
                        'id' => 1,
                        'name' => 'Kévin',
                    ],
                ],
            ],
            'meta' => [
                'itemsPerPage' => 12,
                'currentPage' => 3,
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($paginator, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos?page=3',
            'operation_name' => 'get',
            'uri' => 'http://example.com/foos?page=3',
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizeArray(): void
    {
        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, 'Foo')->willReturn('Foo');
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())
                ->withShortName('Foo')
                ->withOperations(new Operations(['get' => (new GetCollection())])),
        ]));
        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'uri' => 'http://example.com/foos',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
            'root_operation_name' => 'get',
        ])->willReturn([
            'data' => [
                'type' => 'Foo',
                'id' => 1,
                'attributes' => [
                    'id' => 1,
                    'name' => 'Baptiste',
                ],
            ],
        ]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $expected = [
            'links' => ['self' => '/foos'],
            'data' => [
                [
                    'type' => 'Foo',
                    'id' => 1,
                    'attributes' => [
                        'id' => 1,
                        'name' => 'Baptiste',
                    ],
                ],
            ],
            'meta' => ['totalItems' => 1],
        ];

        $this->assertEquals($expected, $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'operation_name' => 'get',
            'uri' => 'http://example.com/foos',
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizeIncludedData(): void
    {
        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, 'Foo')->willReturn('Foo');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())
                ->withShortName('Foo')
                ->withOperations(new Operations(['get' => (new GetCollection())])),
        ]));

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'uri' => 'http://example.com/foos',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
            'root_operation_name' => 'get',
        ])->willReturn([
            'data' => [
                'type' => 'Foo',
                'id' => 1,
                'attributes' => [
                    'id' => 1,
                    'name' => 'Baptiste',
                ],
            ],
            'included' => [
                [
                    'type' => 'Bar',
                    'id' => 1,
                    'attributes' => [
                        'id' => 1,
                        'name' => 'Anto',
                    ],
                ],
            ],
        ]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $expected = [
            'links' => ['self' => '/foos'],
            'data' => [
                [
                    'type' => 'Foo',
                    'id' => 1,
                    'attributes' => [
                        'id' => 1,
                        'name' => 'Baptiste',
                    ],
                ],
            ],
            'meta' => ['totalItems' => 1],
            'included' => [
                [
                    'type' => 'Bar',
                    'id' => 1,
                    'attributes' => [
                        'id' => 1,
                        'name' => 'Anto',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'operation_name' => 'get',
            'uri' => 'http://example.com/foos',
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizeWithoutDataKey(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The JSON API document must contain a "data" key.');

        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, 'Foo')->willReturn('Foo');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())
                ->withShortName('Foo')
                ->withOperations(new Operations(['get' => (new GetCollection())])),
        ]));

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'uri' => 'http://example.com/foos',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
            'root_operation_name' => 'get',
        ])->willReturn([]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'operation_name' => 'get',
            'uri' => 'http://example.com/foos',
            'resource_class' => 'Foo',
        ]);
    }
}
