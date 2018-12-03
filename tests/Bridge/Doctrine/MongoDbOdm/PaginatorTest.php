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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\ODM\MongoDB\CommandCursor;
use Doctrine\ODM\MongoDB\UnitOfWork;
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

    public function testInitializeWithFacetStageNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$facet stage was not applied to the aggregation pipeline.');

        $this->getPaginatorWithMissingStage();
    }

    public function testInitializeWithResultsFacetNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"results" facet was not applied to the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true);
    }

    public function testInitializeWithCountFacetNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"count" facet was not applied to the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true, true);
    }

    public function testInitializeWithSkipStageNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$skip stage was not applied to the facet stage of the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true, true, true);
    }

    public function testInitializeWithLimitStageNotApplied()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$limit stage was not applied to the facet stage of the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true, true, true, true);
    }

    public function testGetIterator()
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    private function getPaginator($firstResult = 1, $maxResults = 15, $totalItems = 42)
    {
        $commandCursor = $this->prophesize(CommandCursor::class);
        $commandCursor->info()->willReturn([
            'query' => [
                'pipeline' => [
                    [
                        '$facet' => [
                            'results' => [
                                ['$skip' => $firstResult],
                                ['$limit' => $maxResults],
                            ],
                            'count' => [
                                ['$count' => 'count'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $commandCursor->toArray()->willReturn([
            [
                'count' => [
                    [
                        'count' => $totalItems,
                    ],
                ],
                'results' => [],
            ],
        ]);

        $unitOfWork = $this->prophesize(UnitOfWork::class);

        return new Paginator($commandCursor->reveal(), $unitOfWork->reveal(), Dummy::class);
    }

    private function getPaginatorWithMissingStage($facet = false, $results = false, $count = false, $maxResults = false)
    {
        $cursorInfo = [
            'query' => [
                'pipeline' => [],
            ],
        ];

        if ($facet) {
            $cursorInfo['query']['pipeline'][] = [
                '$facet' => [],
            ];
        }

        if ($results) {
            $cursorInfo['query']['pipeline'][0]['$facet']['results'] = [];
        }

        if ($count) {
            $cursorInfo['query']['pipeline'][0]['$facet']['count'] = [];
        }

        if ($maxResults) {
            $cursorInfo['query']['pipeline'][0]['$facet']['results'][] = ['$skip' => 42];
        }

        $commandCursor = $this->prophesize(CommandCursor::class);
        $commandCursor->info()->willReturn($cursorInfo);

        $unitOfWork = $this->prophesize(UnitOfWork::class);

        return new Paginator($commandCursor->reveal(), $unitOfWork->reveal(), Dummy::class);
    }

    public function initializeProvider()
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2],
        ];
    }
}
