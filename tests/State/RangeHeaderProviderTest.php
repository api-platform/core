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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\Provider\RangeHeaderProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class RangeHeaderProviderTest extends TestCase
{
    private const UNIT = 'books';

    #[DataProvider('provideIgnoredRequests')]
    public function testIgnoresTheRangeHeaderWhenRfc9110SaysSo(Request $request, Operation $operation): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())
            ->method('provide')
            ->with($this->identicalTo($operation), [], ['request' => $request])
            ->willReturn($paginator = new ArrayPaginator(range(1, 100), 0, 30));

        $this->assertSame($paginator, $this->createProvider($decorated)->provide($operation, [], ['request' => $request]));
        $this->assertFalse($request->attributes->has('_api_filters'));
        $this->assertFalse($request->attributes->has('_api_operation'));
    }

    /**
     * @return iterable<string, array{Request, Operation}>
     */
    public static function provideIgnoredRequests(): iterable
    {
        yield 'no Range header' => [Request::create('/books'), self::createOperation()];
        yield 'no range unit declared on the operation' => [self::createRequest(), new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}')];
        yield 'item operation' => [self::createRequest(), (new Get(shortName: 'Book'))->withRangeUnit(self::UNIT)];
        yield 'HEAD request (§14.2: range handling is defined for GET only)' => [self::createRequest(method: 'HEAD'), self::createOperation()];
        yield 'POST request' => [self::createRequest(method: 'POST'), self::createOperation()];
        yield 'If-Range precondition (§13.1.5: no validator to match)' => [self::createRequest(headers: ['If-Range' => '"abc"']), self::createOperation()];
        yield 'operation not responding with 200' => [self::createRequest(), self::createOperation(status: 202)];
        yield 'unknown range unit' => [self::createRequest('items=0-9'), self::createOperation()];
        yield 'open-ended range' => [self::createRequest('books=10-'), self::createOperation()];
        yield 'suffix range' => [self::createRequest('books=-10'), self::createOperation()];
        yield 'multiple ranges' => [self::createRequest('books=0-9, 20-29'), self::createOperation()];
        yield 'malformed range' => [self::createRequest('not a range'), self::createOperation()];
    }

    public function testTranslatesTheRangeIntoAPageWhateverTheClientPaginationPermissions(): void
    {
        $request = self::createRequest('books=10-19', uri: '/books?title=foo');
        $operation = self::createOperation();
        $paginator = new ArrayPaginator(range(1, 100), 10, 10);

        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())
            ->method('provide')
            ->with($this->callback(static fn (GetCollection $operation): bool => 10 === $operation->getPaginationItemsPerPage()), [], ['request' => $request])
            ->willReturn($paginator);

        $this->assertSame($paginator, $this->createProvider($decorated)->provide($operation, [], ['request' => $request]));
        $this->assertSame(['title' => 'foo', 'page' => 2, 'itemsPerPage' => 10], $request->attributes->get('_api_filters'));

        $operation = $request->attributes->get('_api_operation');
        $this->assertInstanceOf(GetCollection::class, $operation);
        $this->assertSame(206, $operation->getStatus());
        $this->assertSame(10, $operation->getPaginationItemsPerPage());
    }

    public function testOverridesTheClientPaginationParameters(): void
    {
        $request = self::createRequest('BOOKS=0-4', uri: '/books?page=3&itemsPerPage=50');
        $request->attributes->set('_api_filters', ['page' => '3', 'itemsPerPage' => '50', 'author' => 'bar']);
        $operation = self::createOperation(paginationClientItemsPerPage: true);

        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(new ArrayPaginator(range(1, 100), 0, 5));

        $this->createProvider($decorated)->provide($operation, [], ['request' => $request]);

        $this->assertSame(['page' => 1, 'itemsPerPage' => 5, 'author' => 'bar'], $request->attributes->get('_api_filters'));
        $this->assertSame(206, $request->attributes->get('_api_operation')->getStatus());
    }

    public function testDoesNotPromiseAPartialContentWhenTheProviderDoesNotPaginate(): void
    {
        $request = self::createRequest();
        $operation = self::createOperation();

        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn($data = [new \stdClass()]);

        $this->assertSame($data, $this->createProvider($decorated)->provide($operation, [], ['request' => $request]));
        $this->assertNull($request->attributes->get('_api_operation')->getStatus());
    }

    public function testPromisesAPartialContentForAPartialPaginator(): void
    {
        $request = self::createRequest('books=30-59');
        $operation = self::createOperation();

        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(self::createPartialPaginator(range(31, 60), 2, 30));

        $this->createProvider($decorated)->provide($operation, [], ['request' => $request]);

        $this->assertSame(['page' => 2, 'itemsPerPage' => 30], $request->attributes->get('_api_filters'));
        $this->assertSame(206, $request->attributes->get('_api_operation')->getStatus());
    }

    public function testRejectsARangeBeyondTheCollectionWithItsCompleteLength(): void
    {
        $request = self::createRequest('books=30-39');

        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(new ArrayPaginator(range(1, 25), 30, 10));

        try {
            $this->createProvider($decorated)->provide(self::createOperation(), [], ['request' => $request]);
            $this->fail('A 416 exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(416, $e->getStatusCode());
            $this->assertSame(['Content-Range' => 'books */25'], $e->getHeaders());
        }
    }

    public function testRejectsARangeOnAnEmptyCollection(): void
    {
        $request = self::createRequest('books=0-9');

        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(new ArrayPaginator([], 0, 10));

        try {
            $this->createProvider($decorated)->provide(self::createOperation(), [], ['request' => $request]);
            $this->fail('A 416 exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(416, $e->getStatusCode());
            $this->assertSame(['Content-Range' => 'books */0'], $e->getHeaders());
        }
    }

    public function testRejectsARangeBeyondAPartialPaginatorWithoutCompleteLength(): void
    {
        $request = self::createRequest('books=30-39');

        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn(self::createPartialPaginator([], 4, 10));

        try {
            $this->createProvider($decorated)->provide(self::createOperation(), [], ['request' => $request]);
            $this->fail('A 416 exception should have been thrown.');
        } catch (HttpException $e) {
            $this->assertSame(416, $e->getStatusCode());
            $this->assertSame([], $e->getHeaders());
        }
    }

    #[DataProvider('provideInvalidRanges')]
    public function testRejectsAnInvalidRangeBeforeReadingTheCollection(string $range, Operation $operation, string $message): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->never())->method('provide');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage($message);

        try {
            $this->createProvider($decorated)->provide($operation, [], ['request' => self::createRequest($range)]);
        } catch (HttpException $e) {
            $this->assertSame(416, $e->getStatusCode());

            throw $e;
        }
    }

    /**
     * @return iterable<string, array{string, Operation, string}>
     */
    public static function provideInvalidRanges(): iterable
    {
        yield 'first position beyond last position' => ['books=50-20', self::createOperation(), 'The range first position (50) must not exceed its last position (20).'];
        yield 'range not aligned on a page' => ['books=10-25', self::createOperation(), 'The range first position must be a multiple of its length (16).'];
        yield 'range wider than the operation maximum items per page' => ['books=0-9', self::createOperation(paginationMaximumItemsPerPage: 5), 'A range must not span more than 5 books.'];
        yield 'range wider than the global maximum items per page' => ['books=0-99', self::createOperation(), 'A range must not span more than 50 books.'];
    }

    private function createProvider(ProviderInterface $decorated): RangeHeaderProvider
    {
        return new RangeHeaderProvider($decorated, new Pagination(['maximum_items_per_page' => 50]));
    }

    private static function createOperation(mixed ...$arguments): GetCollection
    {
        return new GetCollection(...$arguments + ['shortName' => 'Book', 'uriTemplate' => '/books{._format}', 'rangeUnit' => self::UNIT]);
    }

    /**
     * @param array<string, string> $headers
     */
    private static function createRequest(?string $range = 'books=0-29', string $method = 'GET', array $headers = [], string $uri = '/books'): Request
    {
        $request = Request::create($uri, $method);
        foreach ($headers + ['Range' => $range] as $name => $value) {
            $request->headers->set($name, $value);
        }

        return $request;
    }

    /**
     * @param list<int> $items
     */
    private static function createPartialPaginator(array $items, int $currentPage, int $itemsPerPage): PartialPaginatorInterface
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
