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

namespace ApiPlatform\Meilisearch\Tests;

use ApiPlatform\Meilisearch\Paginator;
use ApiPlatform\Meilisearch\Tests\Fixtures\Movie;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Meilisearch\Search\SearchResult;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PaginatorTest extends TestCase
{
    use ProphecyTrait;

    private const HITS = [
        ['id' => 5, 'title' => 'Fribourg'],
        ['id' => 6, 'title' => 'Lausanne'],
        ['id' => 7, 'title' => 'Vallorbe'],
        ['id' => 8, 'title' => 'Lugano'],
    ];

    private const OFFSET = 4;
    private const LIMIT = 4;

    private PaginatorInterface $paginator;

    protected function setUp(): void
    {
        $this->paginator = $this->getPaginator();
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(PaginatorInterface::class, $this->paginator);
    }

    public function testCount(): void
    {
        self::assertCount(4, $this->paginator);
    }

    public function testGetLastPage(): void
    {
        self::assertSame(2., $this->paginator->getLastPage());
    }

    public function testGetLastPageWithZeroAsLimit(): void
    {
        self::assertSame(1., $this->getPaginator(0, 0)->getLastPage());
    }

    public function testGetLastPageWithNegativeLimit(): void
    {
        self::assertSame(1., $this->getPaginator(-1, 0)->getLastPage());
    }

    public function testGetTotalItems(): void
    {
        self::assertSame(8., $this->paginator->getTotalItems());
    }

    public function testGetCurrentPage(): void
    {
        self::assertSame(2., $this->paginator->getCurrentPage());
    }

    public function testGetCurrentPageWithZeroAsLimit(): void
    {
        self::assertSame(1., $this->getPaginator(0, 0)->getCurrentPage());
    }

    public function testGetItemsPerPage(): void
    {
        self::assertSame(4., $this->paginator->getItemsPerPage());
    }

    public function testGetIterator(): void
    {
        // set local cache
        iterator_to_array($this->paginator);

        self::assertEquals(
            array_map($this->denormalizeMovie(...), self::HITS),
            iterator_to_array($this->paginator)
        );
    }

    private function getPaginator(int $limit = self::LIMIT, int $offset = self::OFFSET, array $hits = self::HITS): Paginator
    {
        $searchResult = new SearchResult([
            'hits' => $hits,
            'processingTimeMs' => 1,
            'query' => '',
            'offset' => $offset,
            'limit' => $limit,
            'estimatedTotalHits' => 8,
        ]);

        $denormalizerProphecy = $this->prophesize(DenormalizerInterface::class);
        foreach ($hits as $hit) {
            $denormalizerProphecy
                ->denormalize($hit, Movie::class, 'array', [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true])
                ->willReturn($this->denormalizeMovie($hit));
        }

        return new Paginator($denormalizerProphecy->reveal(), $searchResult, Movie::class, $limit, $offset);
    }

    private function denormalizeMovie(array $hit): Movie
    {
        $movie = new Movie();
        $movie->id = $hit['id'];
        $movie->title = $hit['title'];

        return $movie;
    }
}
