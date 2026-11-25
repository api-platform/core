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

namespace ApiPlatform\HttpCache\Tests;

use ApiPlatform\HttpCache\Exception\PurgeFailedException;
use ApiPlatform\HttpCache\SouinPurger;
use ApiPlatform\HttpCache\SurrogateKeysPurger;
use ApiPlatform\Metadata\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;

class SurrogateKeysPurgerTest extends TestCase
{
    /**
     * @var array<string, list<string>>
     */
    private array $sent = [];

    /**
     * @param array<string, MockResponse> $responsesByChunk
     */
    private function createClient(string $baseUri, array $responsesByChunk = []): MockHttpClient
    {
        return new MockHttpClient(function (string $method, string $url, array $options) use ($baseUri, $responsesByChunk): MockResponse {
            $chunk = substr($options['normalized_headers']['surrogate-key'][0], \strlen('Surrogate-Key: '));
            $this->sent[$baseUri][] = $chunk;

            return $responsesByChunk[$chunk] ?? new MockResponse();
        }, $baseUri);
    }

    private function catchPurgeFailure(callable $purge): PurgeFailedException
    {
        try {
            $purge();
        } catch (PurgeFailedException $e) {
            return $e;
        }

        $this->fail('Expected a PurgeFailedException to be thrown.');
    }

    public function testEveryClientIsPurgedWhenAnEarlierClientFails(): void
    {
        $clientA = $this->createClient('http://cache-a', ['/foo' => new MockResponse('', ['http_code' => 500])]);
        $clientB = $this->createClient('http://cache-b');

        $e = $this->catchPurgeFailure(static fn () => (new SouinPurger([$clientA, $clientB]))->purge(['/foo']));

        $this->assertSame(['/foo'], $this->sent['http://cache-b']);
        $this->assertCount(1, $e->getFailures());
        $this->assertSame('http://cache-a/', $e->getFailures()[0]->getUrl());
        $this->assertSame('Surrogate-Key', $e->getFailures()[0]->getHeaderName());
        $this->assertSame('/foo', $e->getFailures()[0]->getHeaderValue());
        $this->assertInstanceOf(ServerException::class, $e->getFailures()[0]->getException());
    }

    public function testEveryChunkIsSentWhenAnEarlierChunkFails(): void
    {
        $failing = ['/a2' => new MockResponse('', ['http_code' => 503])];
        $clientA = $this->createClient('http://cache-a', $failing);
        $clientB = $this->createClient('http://cache-b', $failing);

        $e = $this->catchPurgeFailure(static fn () => (new SouinPurger([$clientA, $clientB], 3))->purge(['/a1', '/a2', '/a3']));

        $this->assertSame(['/a1', '/a2', '/a3'], $this->sent['http://cache-a']);
        $this->assertSame(['/a1', '/a2', '/a3'], $this->sent['http://cache-b']);
        $this->assertCount(2, $e->getFailures());
    }

    public function testATransportErrorIsAFailure(): void
    {
        $client = $this->createClient('http://cache-a', ['/foo' => new MockResponse('', ['error' => 'Connection refused'])]);

        $e = $this->catchPurgeFailure(static fn () => (new SouinPurger([$client]))->purge(['/foo']));

        $this->assertCount(1, $e->getFailures());
        $this->assertInstanceOf(TransportException::class, $e->getFailures()[0]->getException());
    }

    public function testEveryFailureIsReportedWithTheFirstAsPrevious(): void
    {
        $clientA = $this->createClient('http://cache-a', ['/a1' => new MockResponse('', ['http_code' => 500])]);
        $clientB = $this->createClient('http://cache-b', [
            '/a1' => new MockResponse('', ['error' => 'Connection refused']),
            '/a3' => new MockResponse('', ['http_code' => 502]),
        ]);

        $e = $this->catchPurgeFailure(static fn () => (new SouinPurger([$clientA, $clientB], 3))->purge(['/a1', '/a2', '/a3']));

        $failures = array_map(static fn ($failure): array => [$failure->getUrl(), $failure->getHeaderValue()], $e->getFailures());
        $this->assertSame([
            ['http://cache-a/', '/a1'],
            ['http://cache-b/', '/a1'],
            ['http://cache-b/', '/a3'],
        ], $failures);
        $this->assertSame($e->getFailures()[0]->getException(), $e->getPrevious());
        $this->assertSame('3 of 6 HTTP cache purge requests failed.', $e->getMessage());
    }

    public function testThePurgeFailureIsAnHttpClientAndApiPlatformException(): void
    {
        $client = $this->createClient('http://cache-a', ['/foo' => new MockResponse('', ['http_code' => 500])]);

        $e = $this->catchPurgeFailure(static fn () => (new SouinPurger([$client]))->purge(['/foo']));

        $this->assertInstanceOf(HttpClientExceptionInterface::class, $e);
        $this->assertInstanceOf(RuntimeException::class, $e);
    }

    public function testNothingIsSentWhenAnyChunkIsTooLong(): void
    {
        $client = $this->createClient('http://cache-a');

        try {
            (new SouinPurger([$client], 10))->purge(['/ok', '/way-too-long-iri']);
            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (RuntimeException $e) {
            $this->assertNotInstanceOf(PurgeFailedException::class, $e);
            $this->assertSame('IRI "/way-too-long-iri" is too long to fit current max header length (currently set to "10"). You can increase it using the "api_platform.http_cache.invalidation.max_header_length" parameter.', $e->getMessage());
        }

        $this->assertSame(0, $client->getRequestsCount());
    }

    public function testEveryChunkReachesEveryClientWhenNothingFails(): void
    {
        $clientA = $this->createClient('http://cache-a');
        $clientB = $this->createClient('http://cache-b');

        (new SurrogateKeysPurger([$clientA, $clientB], 3))->purge(['/a1', '/a2', '/a3']);

        $this->assertSame(['/a1', '/a2', '/a3'], $this->sent['http://cache-a']);
        $this->assertSame(['/a1', '/a2', '/a3'], $this->sent['http://cache-b']);
    }
}
