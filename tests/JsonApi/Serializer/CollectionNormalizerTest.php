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

namespace ApiPlatform\Core\Tests\JsonApi\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\DataProvider\PartialPaginatorInterface;
use ApiPlatform\Core\JsonApi\Serializer\CollectionNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class CollectionNormalizerTest extends TestCase
{
    public function testSupportsNormalize()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');

        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization(new \ArrayObject(), CollectionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization([], 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \ArrayObject(), 'xml'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizePaginator()
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3.);
        $paginatorProphecy->getLastPage()->willReturn(7.);
        $paginatorProphecy->getItemsPerPage()->willReturn(12.);
        $paginatorProphecy->getTotalItems()->willReturn(1312.);
        $paginatorProphecy->rewind()->will(function () {});
        $paginatorProphecy->next()->will(function () {});
        $paginatorProphecy->current()->willReturn('foo');
        $paginatorProphecy->valid()->willReturn(true, false);

        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, 'Foo')->willReturn('Foo');

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos?page=3',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
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

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
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
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizePartialPaginator()
    {
        $paginatorProphecy = $this->prophesize(PartialPaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3.);
        $paginatorProphecy->getItemsPerPage()->willReturn(12.);
        $paginatorProphecy->rewind()->will(function () {});
        $paginatorProphecy->next()->will(function () {});
        $paginatorProphecy->current()->willReturn('foo');
        $paginatorProphecy->valid()->willReturn(true, false);
        $paginatorProphecy->count()->willReturn(1312);

        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, 'Foo')->willReturn('Foo');

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos?page=3',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
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

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
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
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizeArray()
    {
        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, 'Foo')->willReturn('Foo');

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
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

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
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
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizeIncludedData()
    {
        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, 'Foo')->willReturn('Foo');

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
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

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
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
            'resource_class' => 'Foo',
        ]));
    }

    public function testNormalizeWithoutDataKey()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The JSON API document must contain a "data" key.');

        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, 'Foo')->willReturn('Foo');

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'api_sub_level' => true,
            'resource_class' => 'Foo',
        ])->willReturn([]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $normalizer->normalize($data, CollectionNormalizer::FORMAT, [
            'request_uri' => '/foos',
            'resource_class' => 'Foo',
        ]);
    }
}
