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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonApi;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;

#[ApiResource(
    shortName: 'JsonApiPageLinksDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    paginationItemsPerPage: 3,
    paginationClientItemsPerPage: true,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_page_links_dummies',
            provider: [self::class, 'provideTenItems'],
        ),
        new GetCollection(
            uriTemplate: '/jsonapi_page_links_dummies_single_page',
            provider: [self::class, 'provideTwoItems'],
        ),
    ],
)]
class PageLinksDummy
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function provideTenItems(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        return self::paginate(10, $context['filters'] ?? []);
    }

    public static function provideTwoItems(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        return self::paginate(2, $context['filters'] ?? []);
    }

    private static function paginate(int $count, array $filters): ArrayPaginator
    {
        $page = max(1, (int) ($filters['number'] ?? $filters['page'] ?? 1));
        $size = max(1, (int) ($filters['size'] ?? $filters['itemsPerPage'] ?? 3));
        $items = array_map(static fn (int $i): self => new self($i), range(1, $count));

        return new ArrayPaginator($items, ($page - 1) * $size, $size);
    }
}
