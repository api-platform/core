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

use ApiPlatform\State\Pagination\TraversablePartialPaginator;
use PHPUnit\Framework\TestCase;

class TraversablePartialPaginatorTest extends TestCase
{
    public function testInitialize(): void
    {
        $results = [new \stdClass(), new \stdClass(), new \stdClass()];

        $paginator = new TraversablePartialPaginator(new \ArrayIterator($results), 0, 4);

        $this->assertSame(0., $paginator->getCurrentPage());
        $this->assertSame(4., $paginator->getItemsPerPage());
        $this->assertCount(3, $paginator);

        self::assertSame($results, iterator_to_array($paginator));
    }
}
