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
use ApiPlatform\Core\JsonApi\Serializer\CollectionNormalizer;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class CollectionNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportsNormalize()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');

        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization(new \ArrayObject(), CollectionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization([], 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \ArrayObject(), 'xml'));
    }

    public function testNormalizePaginator()
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3.)->shouldBeCalled();
        $paginatorProphecy->getLastPage()->willReturn(7.)->shouldBeCalled();
        $paginatorProphecy->getItemsPerPage()->willReturn(12.)->shouldBeCalled();
        $paginatorProphecy->getTotalItems()->willReturn(1312.)->shouldBeCalled();
        $paginatorProphecy->rewind()->shouldBeCalled();
        $paginatorProphecy->next()->willReturn()->shouldBeCalled();
        $paginatorProphecy->current()->willReturn('foo')->shouldBeCalled();
        $paginatorProphecy->valid()->willReturn(true, false)->shouldBeCalled();

        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, null, true)->willReturn('Foo')->shouldBeCalled();

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer
            ->normalize(
                'foo',
                CollectionNormalizer::FORMAT,
                [
                    'uri' => 'http://localhost/foos?page=3',
                    'api_sub_level' => true,
                    'resource_class' => 'Foo',
                ]
            )
            ->willReturn([
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
                'self' => 'http://localhost/foos?page=3',
                'first' => 'http://localhost/foos?page=1',
                'last' => 'http://localhost/foos?page=7',
                'prev' => 'http://localhost/foos?page=2',
                'next' => 'http://localhost/foos?page=4',
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

        $this->assertEquals($expected, $normalizer->normalize($paginator, CollectionNormalizer::FORMAT, ['uri' => 'http://localhost/foos?page=3']));
    }

    public function testNormalizeArray()
    {
        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, null, true)->willReturn('Foo')->shouldBeCalled();

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer
            ->normalize(
                'foo',
                CollectionNormalizer::FORMAT,
                [
                    'uri' => 'http://localhost/foos',
                    'api_sub_level' => true,
                    'resource_class' => 'Foo',
                ]
            )
            ->willReturn([
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
            'links' => ['self' => 'http://localhost/foos'],
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

        $this->assertEquals($expected, $normalizer->normalize($data, CollectionNormalizer::FORMAT, ['uri' => 'http://localhost/foos']));
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The JSON API document must contain a "data" key.
     */
    public function testNormalizeWithoutDataKey()
    {
        $data = ['foo'];

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($data, null, true)->willReturn('Foo')->shouldBeCalled();

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer
            ->normalize(
                'foo',
                CollectionNormalizer::FORMAT,
                [
                    'uri' => 'http://localhost/foos',
                    'api_sub_level' => true,
                    'resource_class' => 'Foo',
                ]
            )
            ->willReturn([]);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $normalizer->normalize($data, CollectionNormalizer::FORMAT, ['uri' => 'http://localhost/foos']);
    }
}
