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
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use ApiPlatform\State\Processor\RespondProcessor;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * @see https://datatracker.ietf.org/doc/html/rfc9110#section-14.4
 * @see https://datatracker.ietf.org/doc/html/rfc9110#section-14.3
 * @see https://datatracker.ietf.org/doc/html/rfc9110#section-15.3.7
 */
class ContentRangeHeaderTest extends TestCase
{
    use ProphecyTrait;

    public function testContentRangeForPartialCollection(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('books 0-29/201', $response->headers->get('Content-Range'));
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContentRangeForPageThree(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(3.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('books 60-89/201', $response->headers->get('Content-Range'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContentRangeForFullCollection(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(3);
        $paginator->getTotalItems()->willReturn(3.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('books 0-2/3', $response->headers->get('Content-Range'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContentRangeForPartialPaginatorUnknownTotal(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PartialPaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('books 0-29/*', $response->headers->get('Content-Range'));
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContentRangeForEmptyPageKnownTotal(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(0);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('books */201', $response->headers->get('Content-Range'));
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
    }

    public function testNoContentRangeForEmptyPageUnknownTotal(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PartialPaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertNull($response->headers->get('Content-Range'));
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
    }

    public function testContentRangeDoesNotAffectStatusCode(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('books 0-29/201', $response->headers->get('Content-Range'));
    }

    public function testNoContentRangeForNonCollectionOperation(): void
    {
        $operation = new Get(shortName: 'Book');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertNull($response->headers->get('Content-Range'));
        $this->assertNull($response->headers->get('Accept-Ranges'));
    }

    public function testContentRangeWithNoShortNameFallsBackToItems(): void
    {
        $operation = new GetCollection(shortName: null);

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('items 0-29/201', $response->headers->get('Content-Range'));
        $this->assertSame('items', $response->headers->get('Accept-Ranges'));
    }

    public function testHeadRequestReturnsContentRangeHeaders(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}');

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('', $operation, context: [
            'request' => Request::create('/books', 'HEAD'),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame('books 0-29/201', $response->headers->get('Content-Range'));
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
        $this->assertEmpty($response->getContent());
    }

    public function testStatus206WhenOperationStatusIsPartialContent(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}', status: 206);

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(1.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('books 0-29/201', $response->headers->get('Content-Range'));
        $this->assertSame('books', $response->headers->get('Accept-Ranges'));
    }

    public function testStatus206ForPageTwo(): void
    {
        $operation = new GetCollection(shortName: 'Book', uriTemplate: '/books{._format}', status: 206);

        $paginator = $this->prophesize(PaginatorInterface::class);
        $paginator->getCurrentPage()->willReturn(2.0);
        $paginator->getItemsPerPage()->willReturn(30.0);
        $paginator->count()->willReturn(30);
        $paginator->getTotalItems()->willReturn(201.0);

        $respondProcessor = new RespondProcessor();
        $response = $respondProcessor->process('content', $operation, context: [
            'request' => new Request(),
            'original_data' => $paginator->reveal(),
        ]);

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('books 30-59/201', $response->headers->get('Content-Range'));
    }
}
