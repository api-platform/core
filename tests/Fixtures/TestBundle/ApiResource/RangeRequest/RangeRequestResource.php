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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\RangeRequest;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;

#[ApiResource(
    shortName: 'RangeRequest',
    operations: [
        new GetCollection(
            uriTemplate: '/range_requests',
            rangeUnit: 'items',
            provider: [self::class, 'provideCollection'],
        ),
        new GetCollection(
            uriTemplate: '/range_requests_disabled',
            provider: [self::class, 'provideCollection'],
        ),
        new GetCollection(
            uriTemplate: '/range_requests_secured',
            rangeUnit: 'items',
            security: 'is_granted("ROLE_ADMIN")',
            provider: [self::class, 'provideCollection'],
        ),
    ],
    paginationItemsPerPage: 10,
)]
final class RangeRequestResource
{
    public const TOTAL_ITEMS = 25;

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
        $items = array_map(static fn (int $id): self => new self($id), range(1, self::TOTAL_ITEMS));
        $filters = $context['filters'] ?? [];

        if (isset($filters['name'])) {
            $items = array_values(array_filter($items, static fn (self $item): bool => $item->name === $filters['name']));
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $itemsPerPage = $operation->getPaginationItemsPerPage() ?? 10;

        return new ArrayPaginator($items, ($page - 1) * $itemsPerPage, $itemsPerPage);
    }
}
