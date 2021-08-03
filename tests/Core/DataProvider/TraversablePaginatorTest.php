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

namespace ApiPlatform\Core\Tests\DataProvider;

use ApiPlatform\Core\DataProvider\TraversablePaginator;
use ArrayIterator;
use PHPUnit\Framework\TestCase;

class TraversablePaginatorTest extends TestCase
{
    /**
     * @dataProvider initializeProvider
     */
    public function testInitialize(
        array $results,
        float $currentPage,
        float $perPage,
        float $totalItems,
        float $lastPage,
        float $currentItems
    ): void {
        $traversable = new ArrayIterator($results);

        $paginator = new TraversablePaginator($traversable, $currentPage, $perPage, $totalItems);

        self::assertEquals($totalItems, $paginator->getTotalItems());
        self::assertEquals($currentPage, $paginator->getCurrentPage());
        self::assertEquals($lastPage, $paginator->getLastPage());
        self::assertEquals($perPage, $paginator->getItemsPerPage());
        self::assertEquals($currentItems, $paginator->count());

        self::assertSame($results, iterator_to_array($paginator));
    }

    public function initializeProvider(): array
    {
        return [
            'First of three pages of 3 items each' => [[0, 1, 2, 3, 4, 5, 6], 1, 3, 7, 3, 3],
            'Second of two pages of 3 items for the first page and 2 for the second' => [[0, 1, 2, 3, 4], 2, 3, 5, 2, 2],
            'Empty results' => [[], 1, 2, 0, 1, 0],
            '0 items per page' => [[0, 1, 2, 3], 1, 0, 4, 1, 4],
            'Total items less than items per page' => [[0, 1, 2], 1, 4, 3, 1, 3],
        ];
    }
}
