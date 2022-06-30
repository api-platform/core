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

namespace ApiPlatform\Tests\Hal\Serializer;

use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsNormalize()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization([], CollectionNormalizer::FORMAT));
        $this->assertTrue($normalizer->supportsNormalization(new \ArrayObject(), CollectionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization([], 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \ArrayObject(), 'xml'));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    public function testNormalizeApiSubLevel()
    {
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass()->shouldNotBeCalled();
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('bar', null, ['api_sub_level' => true])->willReturn(22);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        $this->assertEquals(['foo' => 22], $normalizer->normalize(['foo' => 'bar'], null, ['api_sub_level' => true]));
    }

    public function testNormalizePaginator()
    {
        $this->assertEquals(
            [
                '_links' => [
                    'self' => ['href' => '/?page=3'],
                    'first' => ['href' => '/?page=1'],
                    'last' => ['href' => '/?page=7'],
                    'prev' => ['href' => '/?page=2'],
                    'next' => ['href' => '/?page=4'],
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
            ],
            $this->normalizePaginator()
        );
    }

    public function testNormalizePartialPaginator()
    {
        $this->assertEquals(
            [
                '_links' => [
                    'self' => ['href' => '/?page=3'],
                    'prev' => ['href' => '/?page=2'],
                    'next' => ['href' => '/?page=4'],
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
                'itemsPerPage' => 12,
            ],
            $this->normalizePaginator(true)
        );
    }

    private function normalizePaginator($partial = false)
    {
        $paginatorProphecy = $this->prophesize(PaginatorInterface::class);
        if ($partial) {
            $paginatorProphecy = $this->prophesize(PartialPaginatorInterface::class);
        }

        $paginatorProphecy->getCurrentPage()->willReturn(3);
        $paginatorProphecy->getItemsPerPage()->willReturn(12);
        $paginatorProphecy->rewind()->will(function () {});
        $paginatorProphecy->valid()->willReturn(true, false);
        $paginatorProphecy->current()->willReturn('foo');
        $paginatorProphecy->next()->will(function () {});

        if (!$partial) {
            $paginatorProphecy->getLastPage()->willReturn(7);
            $paginatorProphecy->getTotalItems()->willReturn(1312);
        } else {
            $paginatorProphecy->count()->willReturn(12);
        }

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass($paginatorProphecy, 'Foo')->willReturn('Foo');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withShortName('Foo')->withOperations(new Operations([
                'bar' => (new GetCollection())->withShortName('Foo'),
            ])),
        ]));

        $itemNormalizer = $this->prophesize(NormalizerInterface::class);
        $itemNormalizer->normalize('foo', CollectionNormalizer::FORMAT, [
            'resource_class' => 'Foo',
            'operation_name' => 'bar',
            'api_sub_level' => true,
        ])->willReturn(['_links' => ['self' => '/me'], 'name' => 'Kévin']);

        $normalizer = new CollectionNormalizer($resourceClassResolverProphecy->reveal(), 'page', $resourceMetadataFactoryProphecy->reveal());
        $normalizer->setNormalizer($itemNormalizer->reveal());

        return $normalizer->normalize($paginatorProphecy->reveal(), CollectionNormalizer::FORMAT, [
            'resource_class' => 'Foo',
            'operation_name' => 'bar',
        ]);
    }
}
