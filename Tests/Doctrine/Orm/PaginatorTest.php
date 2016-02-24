<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Orm;

use Dunglas\ApiBundle\Doctrine\Orm\Paginator;

class PaginatorTest extends \PHPUnit_Framework_TestCase
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

    public function testGetIterator()
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    public function getPaginator($firstResult = 1, $maxResults = 15, $totalItems = 42)
    {
        $query = $this->prophesize('Dunglas\ApiBundle\Tests\Fixtures\Query');
        $query->getFirstResult()->willReturn($firstResult)->shouldBeCalled();
        $query->getMaxResults()->willReturn($maxResults)->shouldBeCalled();

        $doctrinePaginator = $this->prophesize('Doctrine\ORM\Tools\Pagination\Paginator');

        $doctrinePaginator->getQuery()->willReturn($query->reveal())->shouldBeCalled();
        $doctrinePaginator->count()->willReturn($totalItems)->shouldBeCalled();

        $doctrinePaginator->getIterator()->will(function () {
            return new \ArrayIterator();
        });

        return new Paginator($doctrinePaginator->reveal());
    }

    public function initializeProvider()
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2],
        ];
    }
}
