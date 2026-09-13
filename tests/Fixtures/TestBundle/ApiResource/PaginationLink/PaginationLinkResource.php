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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PaginationLink;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\Pagination\ArrayPaginator;

#[ApiResource(
    shortName: 'PaginationLink',
    operations: [
        new GetCollection(
            uriTemplate: '/pagination_links',
            paginationLinkHeader: true,
            provider: [self::class, 'provideCollection'],
        ),
        new GetCollection(
            uriTemplate: '/pagination_links_disabled',
            provider: [self::class, 'provideCollection'],
        ),
        new GetCollection(
            uriTemplate: '/pagination_links_single',
            paginationLinkHeader: true,
            provider: [self::class, 'provideSinglePage'],
        ),
        new GetCollection(
            uriTemplate: '/pagination_links_partial',
            paginationLinkHeader: true,
            paginationPartial: true,
            provider: [self::class, 'providePartialCollection'],
        ),
        new GetCollection(
            uriTemplate: '/pagination_links_client_size',
            paginationLinkHeader: true,
            paginationClientItemsPerPage: true,
            provider: [self::class, 'provideCollection'],
        ),
        new GetCollection(
            uriTemplate: '/pagination_links_fixed_size',
            paginationLinkHeader: true,
            paginationClientItemsPerPage: false,
            provider: [self::class, 'provideCollection'],
        ),
        new GetCollection(
            uriTemplate: '/pagination_links_absolute',
            paginationLinkHeader: true,
            urlGenerationStrategy: UrlGeneratorInterface::ABS_URL,
            provider: [self::class, 'provideCollection'],
        ),
    ],
    paginationItemsPerPage: 10,
)]
final class PaginationLinkResource
{
    public const TOTAL_ITEMS = 25;
    public const SINGLE_PAGE_TOTAL_ITEMS = 5;

    #[ApiProperty(identifier: true)]
    public int $id;

    public string $name;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->name = "Item #{$id}";
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator
    {
        $filters = $context['filters'] ?? [];
        $items = self::filter(self::TOTAL_ITEMS, $filters);
        $itemsPerPage = self::itemsPerPage($operation, $filters);

        return new ArrayPaginator($items, (self::page($filters) - 1) * $itemsPerPage, $itemsPerPage);
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function provideSinglePage(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator
    {
        $filters = $context['filters'] ?? [];
        $items = self::filter(self::SINGLE_PAGE_TOTAL_ITEMS, $filters);
        $itemsPerPage = self::itemsPerPage($operation, $filters);

        return new ArrayPaginator($items, (self::page($filters) - 1) * $itemsPerPage, $itemsPerPage);
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public static function providePartialCollection(Operation $operation, array $uriVariables = [], array $context = []): PartialItems
    {
        $filters = $context['filters'] ?? [];
        $items = self::filter(self::TOTAL_ITEMS, $filters);
        $itemsPerPage = self::itemsPerPage($operation, $filters);
        $page = self::page($filters);

        return new PartialItems(\array_slice($items, ($page - 1) * $itemsPerPage, $itemsPerPage), (float) $page, (float) $itemsPerPage);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<self>
     */
    private static function filter(int $totalItems, array $filters): array
    {
        $items = array_map(static fn (int $id): self => new self($id), range(1, $totalItems));

        if (!isset($filters['name'])) {
            return $items;
        }

        return array_values(array_filter($items, static fn (self $item): bool => $item->name === $filters['name']));
    }

    /**
     * @param array<string, mixed> $filters
     */
    private static function page(array $filters): int
    {
        return max(1, (int) ($filters['page'] ?? 1));
    }

    /**
     * @param array<string, mixed> $filters
     */
    private static function itemsPerPage(Operation $operation, array $filters): int
    {
        $itemsPerPage = $operation->getPaginationItemsPerPage() ?? 10;

        if (true === $operation->getPaginationClientItemsPerPage() && isset($filters['itemsPerPage'])) {
            $itemsPerPage = max(1, (int) $filters['itemsPerPage']);
        }

        return $itemsPerPage;
    }
}
