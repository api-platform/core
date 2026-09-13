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
use ApiPlatform\State\Processor\PaginationLinkProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

final class PaginationLinkProcessorTest extends TestCase
{
    /**
     * @param array<string, mixed> $context
     */
    #[DataProvider('provideGuardsFallThroughCases')]
    public function testAGuardFallsThroughUntouched(Operation $operation, mixed $data, array $context): void
    {
        $processed = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated
            ->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], $context)
            ->willReturn($processed);

        $processor = new PaginationLinkProcessor($decorated, new Pagination());

        $this->assertSame($processed, $processor->process($data, $operation, [], $context));
        $this->assertSame([], self::links($context['request'] ?? null));
    }

    /**
     * @return iterable<string, array{Operation, mixed, array<string, mixed>}>
     */
    public static function provideGuardsFallThroughCases(): iterable
    {
        yield 'no request in the context' => [new GetCollection(paginationLinkHeader: true), self::paginator(2), []];

        yield 'item operation' => [(new Get())->withPaginationLinkHeader(true), self::paginator(2), ['request' => Request::create('/books/1')]];

        yield 'flag not set' => [new GetCollection(), self::paginator(2), ['request' => Request::create('/books?page=2')]];

        yield 'cursor pagination' => [new GetCollection(paginationLinkHeader: true, paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']]), self::paginator(2), ['request' => Request::create('/books?page=2')]];

        yield 'cors preflight' => [new GetCollection(paginationLinkHeader: true), self::paginator(2), ['request' => self::preflightRequest()]];

        yield 'data is not a paginator' => [new GetCollection(paginationLinkHeader: true), new \stdClass(), ['request' => Request::create('/books?page=2')]];
    }

    public function testAMiddlePageAdvertisesEveryRelation(): void
    {
        $request = Request::create('/books?page=2');
        $processor = new PaginationLinkProcessor($this->decorated(), new Pagination());

        $processor->process(self::paginator(2), new GetCollection(paginationLinkHeader: true), [], ['request' => $request]);

        $this->assertSame([
            'self' => '/books?page=2',
            'first' => '/books?page=1',
            'prev' => '/books?page=1',
            'next' => '/books?page=3',
            'last' => '/books?page=3',
        ], self::links($request));
    }

    public function testTheRequestQueryIsCarriedIntoEveryRelationUntouched(): void
    {
        $request = Request::create('/books?itemsPerPage=0');
        $processor = new PaginationLinkProcessor($this->decorated(), new Pagination());

        $processor->process(new ArrayPaginator(range(1, 25), 0, 0), new GetCollection(paginationLinkHeader: true), [], ['request' => $request]);

        $this->assertSame([
            'self' => '/books?itemsPerPage=0&page=1',
            'first' => '/books?itemsPerPage=0&page=1',
            'last' => '/books?itemsPerPage=0&page=1',
        ], self::links($request));
    }

    public function testAnAlreadyProvidedLinkIsPreserved(): void
    {
        $request = Request::create('/books?page=2');
        $request->attributes->set('_api_platform_links', new GenericLinkProvider([new Link('preload', '/style.css')]));
        $processor = new PaginationLinkProcessor($this->decorated(), new Pagination());

        $processor->process(self::paginator(2), new GetCollection(paginationLinkHeader: true), [], ['request' => $request]);

        $links = self::links($request);
        $this->assertSame('/style.css', $links['preload'] ?? null);
        $this->assertCount(6, $links);
    }

    public function testTheOriginalDataWinsOverTheProcessedData(): void
    {
        $request = Request::create('/books?page=2');
        $processor = new PaginationLinkProcessor($this->decorated(), new Pagination());

        $processor->process(new \stdClass(), new GetCollection(paginationLinkHeader: true), [], ['request' => $request, 'original_data' => self::paginator(2)]);

        $this->assertSame([
            'self' => '/books?page=2',
            'first' => '/books?page=1',
            'prev' => '/books?page=1',
            'next' => '/books?page=3',
            'last' => '/books?page=3',
        ], self::links($request));
    }

    /**
     * @return ProcessorInterface<mixed, mixed>
     */
    private function decorated(): ProcessorInterface
    {
        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn(null);

        return $decorated;
    }

    private static function preflightRequest(): Request
    {
        $request = Request::create('/books?page=2', 'OPTIONS');
        $request->headers->set('Access-Control-Request-Method', 'GET');

        return $request;
    }

    private static function paginator(int $page, int $itemsPerPage = 10, int $totalItems = 25): ArrayPaginator
    {
        return new ArrayPaginator(range(1, $totalItems), ($page - 1) * $itemsPerPage, $itemsPerPage);
    }

    /**
     * @return array<string, string>
     */
    private static function links(?Request $request): array
    {
        $links = [];

        foreach ($request?->attributes->get('_api_platform_links')?->getLinks() ?? [] as $link) {
            $links[implode(' ', $link->getRels())] = $link->getHref();
        }

        return $links;
    }
}
