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

namespace ApiPlatform\State\Tests\Processor;

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\Processor\CacheableDocumentationProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheableDocumentationProcessorTest extends TestCase
{
    public function testItSetsEtagAndCacheHeadersOnResponse(): void
    {
        $body = '{"hello":"world"}';
        $processor = new CacheableDocumentationProcessor($this->decoratedReturning(new Response($body)));

        $response = $processor->process(new \stdClass(), new Get(), [], ['request' => new Request()]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('"'.md5($body).'"', $response->getEtag());
        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertTrue($response->headers->hasCacheControlDirective('must-revalidate'));
        $this->assertSame(0, (int) $response->headers->getCacheControlDirective('max-age'));
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testItReturnsNotModifiedWhenIfNoneMatchHeaderMatches(): void
    {
        $body = '{"hello":"world"}';
        $etag = '"'.md5($body).'"';
        $request = new Request();
        $request->headers->set('If-None-Match', $etag);
        $processor = new CacheableDocumentationProcessor($this->decoratedReturning(new Response($body)));

        $response = $processor->process(new \stdClass(), new Get(), [], ['request' => $request]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(304, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
        $this->assertSame($etag, $response->getEtag());
    }

    public function testItPassesThroughWhenDecoratedDoesNotReturnResponse(): void
    {
        $data = new \stdClass();
        $processor = new CacheableDocumentationProcessor($this->decoratedReturning($data));

        $this->assertSame($data, $processor->process($data, new Get(), [], ['request' => new Request()]));
    }

    public function testItDoesNothingForNonOkResponses(): void
    {
        $response = new Response('error', 500);
        $processor = new CacheableDocumentationProcessor($this->decoratedReturning($response));

        $result = $processor->process(new \stdClass(), new Get(), [], ['request' => new Request()]);

        $this->assertSame($response, $result);
        $this->assertNull($result->getEtag());
    }

    public function testItDoesNothingWhenResponseHasNoBody(): void
    {
        $response = new Response('');
        $processor = new CacheableDocumentationProcessor($this->decoratedReturning($response));

        $result = $processor->process(new \stdClass(), new Get(), [], ['request' => new Request()]);

        $this->assertSame($response, $result);
        $this->assertNull($result->getEtag());
    }

    public function testItStillSetsHeadersWhenRequestIsAbsent(): void
    {
        $body = 'payload';
        $processor = new CacheableDocumentationProcessor($this->decoratedReturning(new Response($body)));

        $response = $processor->process(new \stdClass(), new Get(), [], []);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('"'.md5($body).'"', $response->getEtag());
        $this->assertSame(200, $response->getStatusCode());
    }

    private function decoratedReturning(mixed $value): ProcessorInterface
    {
        $decorated = $this->createStub(ProcessorInterface::class);
        $decorated->method('process')->willReturn($value);

        return $decorated;
    }
}
