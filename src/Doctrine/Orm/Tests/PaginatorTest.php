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

namespace ApiPlatform\Doctrine\Orm\Tests;

use ApiPlatform\Doctrine\Orm\Extension\DoctrinePaginatorFactory;
use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Tests\Fixtures\Query as FixturesQuery;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class PaginatorTest extends TestCase
{
    use ProphecyTrait;

    #[\PHPUnit\Framework\Attributes\DataProvider('initializeProvider')]
    public function testInitialize(int $firstResult, int $maxResults, int $totalItems, int $currentPage, int $lastPage, bool $hasNextPage): void
    {
        $paginator = $this->getPaginator($firstResult, $maxResults, $totalItems);

        $this->assertSame((float) $currentPage, $paginator->getCurrentPage());
        $this->assertSame((float) $lastPage, $paginator->getLastPage());
        $this->assertSame((float) $maxResults, $paginator->getItemsPerPage());
        $this->assertSame($hasNextPage, $paginator->hasNextPage());
    }

    public function testInitializeWithQueryFirstResultNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"Doctrine\\ORM\\Query::setFirstResult()" or/and "Doctrine\\ORM\\Query::setMaxResults()" was/were not applied to the query.');

        $this->getPaginatorWithMalformedQuery();
    }

    public function testInitializeWithQueryMaxResultsNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"Doctrine\\ORM\\Query::setFirstResult()" or/and "Doctrine\\ORM\\Query::setMaxResults()" was/were not applied to the query.');

        $this->getPaginatorWithMalformedQuery(true);
    }

    public function testGetIterator(): void
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    private function getPaginator(int $firstResult = 1, int $maxResults = 15, int $totalItems = 42): Paginator
    {
        $query = $this->prophesize($this->getQueryClass());
        $query->getFirstResult()->willReturn($firstResult)->shouldBeCalled();
        $query->getMaxResults()->willReturn($maxResults)->shouldBeCalled();

        $doctrinePaginator = $this->prophesize(DoctrinePaginator::class);

        $doctrinePaginator->getQuery()->willReturn($query->reveal())->shouldBeCalled();
        $doctrinePaginator->count()->willReturn($totalItems);

        $doctrinePaginator->getIterator()->will(fn (): \ArrayIterator => new \ArrayIterator());

        return new Paginator($doctrinePaginator->reveal());
    }

    private function getPaginatorWithMalformedQuery(bool $maxResults = false): void
    {
        $query = $this->prophesize($this->getQueryClass());
        $query->getFirstResult()->willReturn($maxResults ? 42 : -1)->shouldBeCalled();

        if ($maxResults) {
            $query->getMaxResults()->willReturn(null)->shouldBeCalled();
        }

        $doctrinePaginator = $this->prophesize(DoctrinePaginator::class);
        $doctrinePaginator->getQuery()->willReturn($query->reveal())->shouldBeCalled();

        new Paginator($doctrinePaginator->reveal());
    }

    public function testHasNextPageShouldNotMakeQueryIfTotalPagesHasBeenCalled(): void
    {
        $query = $this->prophesize(Query::class);
        $query->getFirstResult()->willReturn(1)->shouldBeCalled();
        $query->getMaxResults()->willReturn(15)->shouldBeCalled();
        $query->setMaxResults(Argument::any())->shouldNotBeCalled();

        $doctrinePaginator = $this->prophesize(DoctrinePaginator::class);

        $doctrinePaginator->getQuery()->willReturn($query->reveal())->shouldBeCalled();
        $doctrinePaginator->count()->willReturn(42);

        $doctrinePaginator->getIterator()->will(fn (): \ArrayIterator => new \ArrayIterator());

        $paginator = new Paginator($doctrinePaginator->reveal());
        $paginator->getTotalItems();
        $this->assertTrue($paginator->hasNextPage());
    }

    public function testHasNextPageShouldMakeQueryIfTotalPagesHasNotBeenCalled(): void
    {
        $em = $this->prophesize(EntityManagerInterface::class);
        $em->getConfiguration()->willReturn(new Configuration());
        $query = new Query($em->reveal());
        $query->setFirstResult(1);
        $query->setMaxResults(15);
        $query->setParameters(new ArrayCollection());
        $query->setCacheable(false);
        $doctrinePaginator = $this->prophesize(DoctrinePaginator::class);

        $doctrinePaginator->getQuery()->willReturn($query)->shouldBeCalled();
        $doctrinePaginator->count()->willReturn(42);
        $doctrinePaginator->getFetchJoinCollection()->willReturn(false);

        $doctrinePaginator->getIterator()->will(fn (): \ArrayIterator => new \ArrayIterator());

        $secondDoctrinePaginator = $this->prophesize(DoctrinePaginator::class);
        $secondDoctrinePaginator->getIterator()->will(fn (): \ArrayIterator => new \ArrayIterator());
        $doctrinePaginatorFactory = $this->prophesize(DoctrinePaginatorFactory::class);
        $doctrinePaginatorFactory->getPaginator(Argument::any(), Argument::any())->willReturn($secondDoctrinePaginator->reveal());

        $paginator = new Paginator($doctrinePaginator->reveal());
        $paginator->setDoctrinePaginatorFactory($doctrinePaginatorFactory->reveal());
        $this->assertFalse($paginator->hasNextPage());
    }

    public static function initializeProvider(): array
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3, true],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2, false],
        ];
    }

    private function getQueryClass(): string
    {
        if ((new \ReflectionClass(Query::class))->isFinal()) {
            return FixturesQuery::class;
        }

        return Query::class;
    }
}
