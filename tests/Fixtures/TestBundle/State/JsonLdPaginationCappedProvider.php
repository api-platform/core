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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\PaginationCapped;

/**
 * Exercises the framework's Pagination service so cap and validation rules apply.
 */
final class JsonLdPaginationCappedProvider implements ProviderInterface
{
    public function __construct(private readonly Pagination $pagination)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $items = array_map(static fn (int $i): PaginationCapped => new PaginationCapped($i), range(1, 80));

        [, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        return new ArrayPaginator($items, $offset, $limit);
    }
}
