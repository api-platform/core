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

namespace ApiPlatform\Doctrine\Common\Tests;

use ApiPlatform\Doctrine\Common\SelectablePartialPaginator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class SelectablePartialPaginatorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('initializeProvider')]
    public function testInitialize($results, $currentPage, $itemsPerPage, $currentItems): void
    {
        $results = new ArrayCollection($results);
        $paginator = new SelectablePartialPaginator($results, $currentPage, $itemsPerPage);

        $this->assertSame((float) $currentPage, $paginator->getCurrentPage());
        $this->assertSame((float) $itemsPerPage, $paginator->getItemsPerPage());
        $this->assertCount($currentItems, $paginator);
    }

    public static function initializeProvider(): array
    {
        return [
            'First of three pages of 3 items each' => [[0, 1, 2, 3, 4, 5, 6], 1, 3, 3],
            'Second of two pages of 3 items for the first page and 2 for the second' => [[0, 1, 2, 3, 4], 2, 3, 2],
            'Empty results' => [[], 1, 2, 0],
            '0 items per page' => [[0, 1, 2, 3], 1, 0, 4],
            'Total items less than items per page' => [[0, 1, 2], 1, 4, 3],
        ];
    }
}
