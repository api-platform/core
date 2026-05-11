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
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;

#[ApiResource(
    shortName: 'JsonApiPaginationDummy',
    formats: ['jsonapi' => ['application/vnd.api+json']],
    paginationItemsPerPage: 3,
    paginationClientItemsPerPage: true,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonapi_pagination_dummies',
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class PaginationDummy
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $items = array_map(static fn (int $i): self => new self($i), range(1, 10));
        $filters = $context['filters'] ?? [];

        $rawPage = $filters['page'] ?? 1;
        if (!is_numeric($rawPage)) {
            throw new InvalidArgumentException('Page must be a positive integer.');
        }
        $page = (int) $rawPage;
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be a positive integer.');
        }

        $itemsPerPage = (int) ($filters['itemsPerPage'] ?? 3);
        if ($itemsPerPage < 1) {
            $itemsPerPage = 3;
        }

        if ($page > intdiv(\PHP_INT_MAX, $itemsPerPage) + 1) {
            throw new InvalidArgumentException('Page is out of range.');
        }
        $offset = ($page - 1) * $itemsPerPage;

        return new ArrayPaginator($items, $offset, $itemsPerPage);
    }
}
