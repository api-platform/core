<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\DataProvider;

use ApiPlatform\Core\DataProvider\TraversablePaginator;
use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Traversable;

class TraversablePaginatorTest extends TestCase
{
    /**
     * @dataProvider initializeProvider
     */
    public function testInitialize(
        Traversable $results,
        float $currentPage,
        float $perPage,
        float $totalItems,
        float $lastPage,
        float $currentItems
    ) {
        $paginator = new TraversablePaginator($results, $currentPage, $perPage, $totalItems);

        self::assertEquals($totalItems, $paginator->getTotalItems());
        self::assertEquals($currentPage, $paginator->getCurrentPage());
        self::assertEquals($lastPage, $paginator->getLastPage());
        self::assertEquals($perPage, $paginator->getItemsPerPage());
        self::assertEquals($currentItems, $paginator->count());
    }

    public function initializeProvider()
    {
        $data = [
            'First of three pages of 3 items each' => [[0, 1, 2, 3, 4, 5, 6], 1, 3, 7, 3, 3],
            'Second of two pages of 3 items for the first page and 2 for the second' => [[0, 1, 2, 3, 4], 2, 3, 5, 2, 2],
            'Empty results' => [[], 1, 2, 0, 1, 0],
            '0 for max results' => [[0, 1, 2, 3], 1, 0, 4, 1, 4],
        ];

        array_walk($data, static function (&$value) {
            $value[0] = new ArrayIterator($value[0]);
        });

        return $data;
    }
}
