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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;

#[ApiResource(
    shortName: 'HalCollectionPaged',
    paginationItemsPerPage: 3,
    paginationClientItemsPerPage: true,
    paginationClientEnabled: true,
    paginationClientPartial: true,
    operations: [
        new GetCollection(
            uriTemplate: '/hal_collection_paged',
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class CollectionPagedResource
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public string $name = '';

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->name = "Dummy #{$id}";
    }

    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $items = array_map(static fn (int $i): self => new self($i), range(1, 10));
        $filters = $context['filters'] ?? [];

        if (isset($filters['id']) && '' !== $filters['id']) {
            $needle = (string) $filters['id'];
            $items = array_values(array_filter($items, static fn (self $r) => (string) $r->id === $needle || "/dummies/{$r->id}" === $needle));
        }

        if (isset($filters['name']) && '' !== $filters['name']) {
            $needle = (string) $filters['name'];
            $items = array_values(array_filter($items, static fn (self $r) => $r->name === $needle));
        }

        $page = (int) ($filters['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $itemsPerPage = (int) ($filters['itemsPerPage'] ?? 3);
        if ($itemsPerPage < 0) {
            $itemsPerPage = 3;
        }

        $paginationDisabled = '0' === (string) ($filters['pagination'] ?? '1');
        if ($paginationDisabled) {
            return new ArrayPaginator($items, 0, \count($items));
        }

        $partial = '1' === (string) ($filters['partial'] ?? '');
        if ($partial) {
            return new HalCollectionPartialPaginator(\array_slice($items, ($page - 1) * $itemsPerPage, $itemsPerPage), $page, $itemsPerPage);
        }

        return new ArrayPaginator($items, ($page - 1) * $itemsPerPage, $itemsPerPage);
    }
}

/**
 * @internal
 */
final class HalCollectionPartialPaginator implements \IteratorAggregate, PartialPaginatorInterface, HasNextPagePaginatorInterface
{
    /** @param list<CollectionPagedResource> $items */
    public function __construct(private readonly array $items, private readonly int $page, private readonly int $itemsPerPage)
    {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getCurrentPage(): float
    {
        return (float) $this->page;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    public function hasNextPage(): bool
    {
        return \count($this->items) === $this->itemsPerPage;
    }
}
