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

namespace ApiPlatform\Tests\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\Processor\RespondProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContentRangeHeaderTest extends TestCase
{
    public function testAdvertisesTheRangeUnitOnAFullResponse(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books'), new ArrayPaginator(range(1, 201), 0, 30));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'), 'RFC 9110 §14.4: Content-Range is only meaningful on 206 and 416 responses.');
    }

    public function testAdvertisesTheRangeUnitOnAHeadResponse(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books'), new ArrayPaginator(range(1, 201), 0, 30), Request::create('/books', 'HEAD'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testDescribesThePartialContent(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books', status: 206), new ArrayPaginator(range(1, 201), 60, 30));

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertSame('books 60-89/201', $response->headers->get('Content-Range'));
    }

    public function testDescribesTheLastPartialContent(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books', status: 206), new ArrayPaginator(range(1, 201), 180, 30));

        $this->assertSame('books 180-200/201', $response->headers->get('Content-Range'));
    }

    public function testDescribesThePartialContentOfUnknownCompleteLength(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books', status: 206), $this->createPartialPaginator(range(31, 60), 2, 30));

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('books 30-59/*', $response->headers->get('Content-Range'));
    }

    public function testDoesNotDescribeAnEmptyPartialContent(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books', status: 206), new ArrayPaginator([], 0, 30));

        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testDoesNothingWithoutARangeUnit(): void
    {
        $response = $this->respond(new GetCollection(status: 206), new ArrayPaginator(range(1, 201), 0, 30));

        $this->assertFalse($response->headers->has('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testDoesNothingOnAnItemOperation(): void
    {
        $response = $this->respond((new Get())->withRangeUnit('books'), new ArrayPaginator(range(1, 201), 0, 30));

        $this->assertFalse($response->headers->has('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testDoesNothingWhenTheProviderDoesNotPaginate(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books', status: 206), [new \stdClass()]);

        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    public function testDoesNotAdvertiseTheRangeUnitOutsideOfASuccessfulResponse(): void
    {
        $response = $this->respond(new GetCollection(rangeUnit: 'books', status: 204), new ArrayPaginator(range(1, 201), 0, 30));

        $this->assertFalse($response->headers->has('Accept-Ranges'));
        $this->assertFalse($response->headers->has('Content-Range'));
    }

    private function respond(Get|GetCollection $operation, mixed $originalData, ?Request $request = null): Response
    {
        return (new RespondProcessor())->process('content', $operation, context: [
            'request' => $request ?? Request::create('/books'),
            'original_data' => $originalData,
        ]);
    }

    /**
     * @param list<int> $items
     */
    private function createPartialPaginator(array $items, int $currentPage, int $itemsPerPage): PartialPaginatorInterface
    {
        return new class($items, $currentPage, $itemsPerPage) implements \IteratorAggregate, PartialPaginatorInterface {
            /**
             * @param list<int> $items
             */
            public function __construct(private readonly array $items, private readonly int $currentPage, private readonly int $itemsPerPage)
            {
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->items);
            }

            public function count(): int
            {
                return \count($this->items);
            }

            public function getCurrentPage(): float
            {
                return $this->currentPage;
            }

            public function getItemsPerPage(): float
            {
                return $this->itemsPerPage;
            }
        };
    }
}
