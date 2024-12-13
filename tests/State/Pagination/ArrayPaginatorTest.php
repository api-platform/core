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

namespace ApiPlatform\Tests\State\Pagination;

use ApiPlatform\State\Pagination\ArrayPaginator;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ArrayPaginatorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('initializeProvider')]
    public function testInitialize(array $results, $firstResult, $maxResults, $currentItems, $totalItems, $currentPage, $lastPage, $hasNextPage): void
    {
        $paginator = new ArrayPaginator($results, $firstResult, $maxResults);

        $this->assertSame((float) $totalItems, $paginator->getTotalItems());
        $this->assertSame((float) $currentPage, $paginator->getCurrentPage());
        $this->assertSame((float) $lastPage, $paginator->getLastPage());
        $this->assertSame((float) $maxResults, $paginator->getItemsPerPage());
        $this->assertCount($currentItems, $paginator);
        $this->assertSame($hasNextPage, $paginator->hasNextPage());
    }

    public static function initializeProvider(): array
    {
        return [
            'First of three pages of 3 items each' => [[0, 1, 2, 3, 4, 5, 6], 0, 3, 3, 7, 1, 3, true],
            'Second of two pages of 3 items for the first page and 2 for the second' => [[0, 1, 2, 3, 4], 3, 3, 2, 5, 2, 2, false],
            'Empty results' => [[], 0, 2, 0, 0, 1, 1, false],
            '0 for max results' => [[0, 1, 2, 3], 2, 0, 0, 4, 1, 1, false],
            'First result greater than total items' => [[0, 1], 2, 1, 0, 2, 3, 2, false],
        ];
    }
}
