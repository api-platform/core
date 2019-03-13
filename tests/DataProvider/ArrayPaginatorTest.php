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

use ApiPlatform\Core\DataProvider\ArrayPaginator;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ArrayPaginatorTest extends TestCase
{
    /**
     * @dataProvider initializeProvider
     */
    public function testInitialize(array $results, $firstResult, $maxResults, $currentItems, $totalItems, $currentPage, $lastPage)
    {
        $paginator = new ArrayPaginator($results, $firstResult, $maxResults);

        $this->assertEquals($totalItems, $paginator->getTotalItems());
        $this->assertEquals($currentPage, $paginator->getCurrentPage());
        $this->assertEquals($lastPage, $paginator->getLastPage());
        $this->assertEquals($maxResults, $paginator->getItemsPerPage());
        $this->assertEquals($currentItems, $paginator->count());
    }

    public function initializeProvider()
    {
        return [
            'First of three pages of 3 items each' => [[0, 1, 2, 3, 4, 5, 6], 0, 3, 3, 7, 1, 3],
            'Second of two pages of 3 items for the first page and 2 for the second' => [[0, 1, 2, 3, 4], 3, 3, 2, 5, 2, 2],
            'Empty results' => [[], 0, 2, 0, 0, 1, 1],
            '0 for max results' => [[0, 1, 2, 3], 2, 0, 0, 4, 1, 1],
        ];
    }
}
