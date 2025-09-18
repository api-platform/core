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

use ApiPlatform\Hal\Serializer\CollectionNormalizer;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CollectionNormalizerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testSupportsNormalize(): void
    {
        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceMetadataFactoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $normalizer = new CollectionNormalizer($resourceClassResolverMock, 'page', $resourceMetadataFactoryMock);

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

    public function testNormalizePartialPaginator(): void
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

    private function normalizePaginator(bool $partial = false): array
    {
        if ($partial) {
            $paginator = $this->createMock(PartialPaginatorInterface::class);
        } else {
            $paginator = $this->createMock(PaginatorInterface::class);
        }

        $paginator->method('getCurrentPage')->willReturn(3.);
        $paginator->method('getItemsPerPage')->willReturn(12.);
        $paginator->method('valid')->willReturnOnConsecutiveCalls(true, false); // @phpstan-ignore-line
        $paginator->method('current')->willReturn('foo'); // @phpstan-ignore-line

        if (!$partial) {
            $paginator->method('getLastPage')->willReturn(7.); // @phpstan-ignore-line
            $paginator->method('getTotalItems')->willReturn(1312.); // @phpstan-ignore-line
        } else {
            $paginator->method('count')->willReturn(12);
        }

        $resourceClassResolverMock = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolverMock->method('getResourceClass')->with($paginator, 'Foo')->willReturn('Foo');

        $resourceMetadataFactoryMock = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryMock->method('create')->with('Foo')->willReturn(new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withShortName('Foo')->withOperations(new Operations([
                'bar' => (new GetCollection())->withShortName('Foo'),
            ])),
        ]));

        $itemNormalizer = $this->createMock(NormalizerInterface::class);
        $itemNormalizer->method('normalize')->with('foo', CollectionNormalizer::FORMAT, [
            'resource_class' => 'Foo',
            'api_sub_level' => true,
            'root_operation_name' => 'bar',
        ])->willReturn(['_links' => ['self' => '/me'], 'name' => 'Kévin']);

        $normalizer = new CollectionNormalizer($resourceClassResolverMock, 'page', $resourceMetadataFactoryMock);
        $normalizer->setNormalizer($itemNormalizer);

        return $normalizer->normalize($paginator, CollectionNormalizer::FORMAT, [
            'resource_class' => 'Foo',
            'operation_name' => 'bar',
        ]);
    }
}
