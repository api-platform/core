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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Test\DoctrineMongoDbOdmSetup;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
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

    public function testInitializeWithLimitZeroStageApplied()
    {
        $paginator = $this->getPaginator(0, 5, 0, true);

        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertEquals(1, $paginator->getLastPage());
        $this->assertEquals(0, $paginator->getItemsPerPage());
    }

    public function testInitializeWithNoCount()
    {
        $paginator = $this->getPaginatorWithNoCount();

        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertEquals(1, $paginator->getLastPage());
        $this->assertEquals(15, $paginator->getItemsPerPage());
    }

    public function testGetIterator()
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    private function getPaginator($firstResult = 1, $maxResults = 15, $totalItems = 42, $limitZero = false)
    {
        $iterator = $this->prophesize(Iterator::class);
        $pipeline = [
            [
                '$facet' => [
                    'results' => [
                        ['$skip' => $firstResult],
                        $limitZero ? ['$match' => [Paginator::LIMIT_ZERO_MARKER_FIELD => Paginator::LIMIT_ZERO_MARKER]] : ['$limit' => $maxResults],
                    ],
                    'count' => [
                        ['$count' => 'count'],
                    ],
                ],
            ],
        ];
        $iterator->toArray()->willReturn([
            [
                'count' => [
                    [
                        'count' => $totalItems,
                    ],
                ],
                'results' => [],
            ],
        ]);

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAnnotationMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class, $pipeline);
    }

    private function getPaginatorWithMissingStage($facet = false, $results = false, $count = false, $maxResults = false)
    {
        $pipeline = [];

        if ($facet) {
            $pipeline[] = [
                '$facet' => [],
            ];
        }

        if ($results) {
            $pipeline[0]['$facet']['results'] = [];
        }

        if ($count) {
            $pipeline[0]['$facet']['count'] = [];
        }

        if ($maxResults) {
            $pipeline[0]['$facet']['results'][] = ['$skip' => 42];
        }

        $iterator = $this->prophesize(Iterator::class);

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAnnotationMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class, $pipeline);
    }

    private function getPaginatorWithNoCount($firstResult = 1, $maxResults = 15)
    {
        $iterator = $this->prophesize(Iterator::class);
        $pipeline = [
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
        ];
        $iterator->toArray()->willReturn([
            [
                'count' => [],
                'results' => [],
            ],
        ]);

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAnnotationMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class, $pipeline);
    }

    public function initializeProvider()
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2],
        ];
    }
}
