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
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Provider\RangeHeaderProvider;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RangeHeaderProviderTest extends TestCase
{
    private function createProvider(?ProviderInterface $decorated = null): RangeHeaderProvider
    {
        $decorated ??= $this->createStub(ProviderInterface::class);
        $pagination = new Pagination();

        return new RangeHeaderProvider($decorated, $pagination);
    }

    public function testDelegatesWhenNoRangeHeader(): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn([]);

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $result = $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => new Request()]);

        $this->assertSame([], $result);
    }

    public function testDelegatesWhenNotCollectionOperation(): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(null);

        $request = new Request();
        $request->headers->set('Range', 'books=0-29');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new Get(shortName: 'Book'), [], ['request' => $request]);
    }

    public function testDelegatesWhenNotGetOrHead(): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn(null);

        $request = Request::create('/books', 'POST');
        $request->headers->set('Range', 'books=0-29');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);
    }

    public function testIgnoresUnparseableRangeFormat(): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn([]);

        $request = new Request();
        $request->headers->set('Range', 'invalid-format');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);
    }

    public function testIgnoresWrongUnit(): void
    {
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated->expects($this->once())->method('provide')->willReturn([]);

        $request = new Request();
        $request->headers->set('Range', 'items=0-29');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);
    }

    public function testHeadRequestWithRangeHeaderSetsFilters(): void
    {
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([]);

        $request = Request::create('/books', 'HEAD');
        $request->headers->set('Range', 'books=0-29');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);

        $filters = $request->attributes->get('_api_filters');
        $this->assertSame(1, $filters['page']);
        $this->assertSame(30, $filters['itemsPerPage']);

        $operation = $request->attributes->get('_api_operation');
        $this->assertSame(206, $operation->getStatus());
    }

    public function testValidRangeSetsFiltersAndStatus206(): void
    {
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([]);

        $request = new Request();
        $request->headers->set('Range', 'books=0-29');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);

        $filters = $request->attributes->get('_api_filters');
        $this->assertSame(1, $filters['page']);
        $this->assertSame(30, $filters['itemsPerPage']);

        $operation = $request->attributes->get('_api_operation');
        $this->assertSame(206, $operation->getStatus());
    }

    public function testValidRangePageTwo(): void
    {
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated->method('provide')->willReturn([]);

        $request = new Request();
        $request->headers->set('Range', 'books=30-59');

        $provider = new RangeHeaderProvider($decorated, new Pagination());
        $provider->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);

        $filters = $request->attributes->get('_api_filters');
        $this->assertSame(2, $filters['page']);
        $this->assertSame(30, $filters['itemsPerPage']);
    }

    public function testStartGreaterThanEndThrows416(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Range start must not exceed end.');

        $request = new Request();
        $request->headers->set('Range', 'books=50-20');

        $this->createProvider()->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);
    }

    public function testNonPageAlignedRangeThrows416(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Range must be aligned to page boundaries.');

        $request = new Request();
        $request->headers->set('Range', 'books=10-25');

        $this->createProvider()->provide(new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}'), [], ['request' => $request]);
    }
}
