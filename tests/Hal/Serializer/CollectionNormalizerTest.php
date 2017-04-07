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

namespace ApiPlatform\Core\Tests\Hal\Serializer;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Hal\Serializer\CollectionNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
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

    public function testNormalizeApiSubLevel()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass()->shouldNotBeCalled();

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('bar', null, ['api_sub_level' => true])->willReturn(22);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $this->assertEquals(['foo' => 22], $normalizer->normalize(['foo' => 'bar'], null, ['api_sub_level' => true]));
    }

    public function testNormalizePaginator()
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        $paginatorProphecy->getCurrentPage()->willReturn(3);
        $paginatorProphecy->getLastPage()->willReturn(7);
        $paginatorProphecy->getItemsPerPage()->willReturn(12);
        $paginatorProphecy->getTotalItems()->willReturn(1312);
        $paginatorProphecy->rewind()->shouldBeCalled();
        $paginatorProphecy->valid()->willReturn(true, false)->shouldBeCalled();
        $paginatorProphecy->current()->willReturn('foo')->shouldBeCalled();
        $paginatorProphecy->next()->willReturn()->shouldBeCalled();
        $paginator = $paginatorProphecy->reveal();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginator, null, true)->willReturn('Foo')->shouldBeCalled();

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', null, ['api_sub_level' => true, 'resource_class' => 'Foo'])->willReturn(['_links' => ['self' => '/me'], 'name' => 'Kévin']);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page');
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $expected = [
            '_links' => [
                'self' => '/?page=3',
                'first' => '/?page=1',
                'last' => '/?page=7',
                'prev' => '/?page=2',
                'next' => '/?page=4',
                'item' => [
                        '/me',
                    ],
            ],
            '_embedded' => [
                    'item' => [
                        [
                            '_links' => [
                                    'self' => '/me',
                                ],
                            'name' => 'Kévin',
                        ],
                    ],
            ],
            'totalItems' => 1312,
            'itemsPerPage' => 12,
        ];
        $this->assertEquals($expected, $normalizer->normalize($paginator));
    }
}
