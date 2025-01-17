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
use ApiPlatform\Metadata\Exception\RuntimeException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
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

    public function testInitializeWithNoCount(): void
    {
        $paginator = $this->getPaginatorWithNoCount();

        $this->assertSame(1., $paginator->getCurrentPage());
        $this->assertSame(1., $paginator->getLastPage());
        $this->assertSame(15., $paginator->getItemsPerPage());
    }

    #[TestWith(['__api_first_result__'])]
    #[TestWith(['__api_max_results__'])]
    #[TestWith(['results'])]
    #[TestWith(['count'])]
    public function testInitializeWithMissingResultField(string $missingField): void
    {
        $this->expectException(RuntimeException::class);

        $this->getPaginatorMissingResultField($missingField);
    }

    public function testGetIterator(): void
    {
        $paginator = $this->getPaginator();

        $this->assertSame($paginator->getIterator(), $paginator->getIterator(), 'Iterator should be cached');
    }

    private function getPaginator(int $firstResult = 1, int $maxResults = 15, int $totalItems = 42): Paginator
    {
        $iterator = $this->prophesize(Iterator::class);
        $iterator->toArray()->willReturn([
            [
                'count' => [['count' => $totalItems]],
                'results' => [],
                '__api_first_result__' => $firstResult,
                '__api_max_results__' => $maxResults,
            ],
        ]);

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class);
    }

    private function getPaginatorWithNoCount(): Paginator
    {
        $iterator = $this->prophesize(Iterator::class);
        $iterator->toArray()->willReturn([
            [
                'count' => [],
                'results' => [],
                '__api_first_result__' => 1,
                '__api_max_results__' => 15,
            ],
        ]);

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class);
    }

    private function getPaginatorMissingResultField(string $missing): Paginator
    {
        $iterator = $this->prophesize(Iterator::class);
        $iterator->toArray()->willReturn([
            array_diff_key([
                'count' => [['count' => 42]],
                'results' => [],
                '__api_first_result__' => 1,
                '__api_max_results__' => 15,
            ], [$missing => 1]),
        ]);

        $fixturesPath = \dirname((string) (new \ReflectionClass(Dummy::class))->getFileName());
        $config = DoctrineMongoDbOdmSetup::createAttributeMetadataConfiguration([$fixturesPath], true);
        $documentManager = DocumentManager::create(null, $config);

        return new Paginator($iterator->reveal(), $documentManager->getUnitOfWork(), Dummy::class);
    }

    public static function initializeProvider(): array
    {
        return [
            'First of three pages of 15 items each' => [0, 15, 42, 1, 3, true],
            'Second of two pages of 10 items each' => [10, 10, 20, 2, 2, false],
        ];
    }
}
