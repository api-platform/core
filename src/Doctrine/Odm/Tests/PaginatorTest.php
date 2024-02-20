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

namespace ApiPlatform\Doctrine\Odm\Tests;

use ApiPlatform\Doctrine\Odm\Paginator;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @group mongodb
 */
class PaginatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider initializeProvider
     */
    public function testInitialize(int $firstResult, int $maxResults, int $totalItems, int $currentPage, int $lastPage, bool $hasNextPage): void
    {
        $paginator = $this->getPaginator($firstResult, $maxResults, $totalItems);

        $this->assertSame((float) $currentPage, $paginator->getCurrentPage());
        $this->assertSame((float) $lastPage, $paginator->getLastPage());
        $this->assertSame((float) $maxResults, $paginator->getItemsPerPage());
        $this->assertSame($hasNextPage, $paginator->hasNextPage());
    }

    public function testInitializeWithFacetStageNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$facet stage was not applied to the aggregation pipeline.');

        $this->getPaginatorWithMissingStage();
    }

    public function testInitializeWithResultsFacetNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"results" facet was not applied to the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true);
    }

    public function testInitializeWithCountFacetNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"count" facet was not applied to the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true, true);
    }

    public function testInitializeWithSkipStageNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$skip stage was not applied to the facet stage of the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true, true, true);
    }

    public function testInitializeWithLimitStageNotApplied(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$limit stage was not applied to the facet stage of the aggregation pipeline.');

        $this->getPaginatorWithMissingStage(true, true, true, true);
    }

    public function testInitializeWithLimitZeroStageApplied(): void
    {
        $paginator = $this->getPaginator(0, 5, 0, true);

        $this->assertSame(1., $paginator->getCurrentPage());
        $this->assertSame(1., $paginator->getLastPage());
        $this->assertSame(0., $paginator->getItemsPerPage());
    }

    public function testInitializeWithNoCount(): void
    {
        $paginator = $this->getPaginatorWithNoCount();

        $this->assertSame(1., $paginator->getCurrentPage());
        $this->assertSame(1., $paginator->getLastPage());
        $this->assertSame(15., $paginator->getItemsPerPage());
    }

    public function testGetIterator(): void
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    private function getPaginator(int $firstResult = 1, int $maxResults = 15, int $totalItems = 42, bool $limitZero = false): Paginator
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
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class, $pipeline);
    }

    private function getPaginatorWithMissingStage(bool $facet = false, bool $results = false, bool $count = false, bool $maxResults = false): Paginator
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
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class, $pipeline);
    }

    private function getPaginatorWithNoCount($firstResult = 1, $maxResults = 15): Paginator
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
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class, $pipeline);
    }

    public static function initializeProvider(): array
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3, true],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2, false],
        ];
    }
}
