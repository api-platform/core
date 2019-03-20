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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Tests\Fixtures\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    /**
     * @dataProvider initializeProvider
     */
    public function testInitialize($firstResult, $maxResults, $totalItems, $currentPage, $lastPage)
    {
        $paginator = $this->getPaginator($firstResult, $maxResults, $totalItems);

        $this->assertEquals($currentPage, $paginator->getCurrentPage());
        $this->assertEquals($lastPage, $paginator->getLastPage());
        $this->assertEquals($maxResults, $paginator->getItemsPerPage());
    }

    public function testInitializeWithQueryFirstResultNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"Doctrine\\ORM\\Query::setFirstResult()" or/and "Doctrine\\ORM\\Query::setMaxResults()" was/were not applied to the query.');

        $this->getPaginatorWithMalformedQuery();
    }

    public function testInitializeWithQueryMaxResultsNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"Doctrine\\ORM\\Query::setFirstResult()" or/and "Doctrine\\ORM\\Query::setMaxResults()" was/were not applied to the query.');

        $this->getPaginatorWithMalformedQuery(true);
    }

    public function testGetIterator()
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    private function getPaginator($firstResult = 1, $maxResults = 15, $totalItems = 42)
    {
        $query = $this->prophesize(Query::class);
        $query->getFirstResult()->willReturn($firstResult)->shouldBeCalled();
        $query->getMaxResults()->willReturn($maxResults)->shouldBeCalled();

        $doctrinePaginator = $this->prophesize(DoctrinePaginator::class);

        $doctrinePaginator->getQuery()->willReturn($query->reveal())->shouldBeCalled();
        $doctrinePaginator->count()->willReturn($totalItems);

        $doctrinePaginator->getIterator()->will(function () {
            return new \ArrayIterator();
        });

        return new Paginator($doctrinePaginator->reveal());
    }

    private function getPaginatorWithMalformedQuery($maxResults = false)
    {
        $query = $this->prophesize(Query::class);
        $query->getFirstResult()->willReturn($maxResults ? 42 : null)->shouldBeCalled();

        if ($maxResults) {
            $query->getMaxResults()->willReturn(null)->shouldBeCalled();
        }

        $doctrinePaginator = $this->prophesize(DoctrinePaginator::class);
        $doctrinePaginator->getQuery()->willReturn($query->reveal())->shouldBeCalled();

        new Paginator($doctrinePaginator->reveal());
    }

    public function initializeProvider()
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2],
        ];
    }
}
