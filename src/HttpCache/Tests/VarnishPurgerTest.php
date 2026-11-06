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
use ApiPlatform\HttpCache\VarnishPurger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishPurgerTest extends TestCase
{
    use ProphecyTrait;

    public function testPurge(): void
    {
        $clientProphecy1 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo|/bar)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo|/bar)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo|/bar)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo|/bar)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();

        $clientProphecy3 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy3->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();
        $clientProphecy3->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/bar)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/bar)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();

        $clientProphecy4 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy4->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();
        $clientProphecy4->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/bar)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/bar)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);

        $purger = new VarnishPurger([$clientProphecy3->reveal(), $clientProphecy4->reveal()], 12);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    public function testEmptyTags(): void
    {
        $clientProphecy1 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy1->request()->shouldNotBeCalled();

        $client = $clientProphecy1->reveal();
        $purger = new VarnishPurger([$client]);
        $purger->purge([]);
    }

    #[DataProvider('provideChunkHeaderCases')]
    public function testItChunksHeaderToAvoidHittingVarnishLimit(int $maxHeaderLength, array $iris, array $regexesToSend): void
    {
        $client = new class implements HttpClientInterface {
            public array $sentRegexes = [];

            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $this->sentRegexes[] = $options['headers']['ApiPlatform-Ban-Regex'];

                return new MockResponse();
            }

            public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function withOptions(array $options): static
            {
                return $this;
            }
        };

        $purger = new VarnishPurger([$client], $maxHeaderLength);
        $purger->purge($iris);

        self::assertSame($regexesToSend, $client->sentRegexes);
    }

    public static function provideChunkHeaderCases(): \Generator
    {
        yield 'no iri' => [
            50,
            [],
            [],
        ];

        yield 'one iri' => [
            50,
            ['/foo'],
            ['(/foo)($|\,)'],
        ];

        yield 'few iris' => [
            50,
            ['/foo', '/bar'],
            ['(/foo|/bar)($|\,)'],
        ];

        yield 'iris to generate a header with exactly the maximum length' => [
            22,
            ['/foo', '/bar', '/baz'],
            ['(/foo|/bar|/baz)($|\,)'],
        ];

        yield 'iris to generate a header with exactly the maximum length and a smaller one' => [
            17,
            ['/foo', '/bar', '/baz'],
            [
                '(/foo|/bar)($|\,)',
                '(/baz)($|\,)',
            ],
        ];

        yield 'with last iri too long to be part of the same header' => [
            35,
            ['/foo', '/bar', '/some-longer-tag'],
            [
                '(/foo|/bar)($|\,)',
                '(/some\-longer\-tag)($|\,)',
            ],
        ];

        yield 'iris to have five headers' => [
            25,
            ['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6', '/foo/7', '/foo/8', '/foo/9', '/foo/10'],
            [
                '(/foo/1|/foo/2)($|\,)',
                '(/foo/3|/foo/4)($|\,)',
                '(/foo/5|/foo/6)($|\,)',
                '(/foo/7|/foo/8)($|\,)',
                '(/foo/9|/foo/10)($|\,)',
            ],
        ];

        yield 'with varnish default limit' => [
            8000,
            array_fill(0, 3000, '/foo'),
            [
                \sprintf('(%s)($|\,)', implode('|', array_fill(0, 1598, '/foo'))),
                \sprintf('(%s)($|\,)', implode('|', array_fill(0, 1402, '/foo'))),
            ],
        ];
    }

    public function testConstructor(): void
    {
        $clientProphecy = $this->prophesize(HttpClientInterface::class);
        $clientProphecy->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)'], 'user_data' => ['ApiPlatform-Ban-Regex', '(/foo)($|\,)']])->willReturn(new MockResponse())->shouldBeCalled();
        $purger = new VarnishPurger(new RewindableGenerator(static function () use ($clientProphecy) {
            yield $clientProphecy->reveal();
        }, 1));

        $purger->purge(['/foo']);
    }

    public function testGetResponseHeader(): void
    {
        $clientProphecy = $this->prophesize(HttpClientInterface::class);

        $purger = new VarnishPurger([$clientProphecy->reveal()]);
        self::assertSame(['Cache-Tags' => '/foo'], $purger->getResponseHeaders(['/foo']));
    }

    /**
     * @param array<string, list<string>> $sent
     * @param array<string, MockResponse> $responsesByRegex
     */
    private function createRecordingClient(string $baseUri, array &$sent, array $responsesByRegex = []): MockHttpClient
    {
        return new MockHttpClient(static function (string $method, string $url, array $options) use ($baseUri, &$sent, $responsesByRegex): MockResponse {
            $regex = substr($options['normalized_headers']['apiplatform-ban-regex'][0], \strlen('ApiPlatform-Ban-Regex: '));
            $sent[$baseUri][] = $regex;

            return $responsesByRegex[$regex] ?? new MockResponse();
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

    public function testEveryClientIsBannedWhenAnEarlierClientFails(): void
    {
        $sent = [];
        $clientA = $this->createRecordingClient('http://varnish-a', $sent, ['(/foo)($|\\,)' => new MockResponse('', ['http_code' => 500])]);
        $clientB = $this->createRecordingClient('http://varnish-b', $sent);

        $e = $this->catchPurgeFailure(static fn () => (new VarnishPurger([$clientA, $clientB]))->purge(['/foo']));

        $this->assertSame(['(/foo)($|\\,)'], $sent['http://varnish-b']);
        $this->assertCount(1, $e->getFailures());
        $this->assertSame('http://varnish-a/', $e->getFailures()[0]->getUrl());
        $this->assertSame('ApiPlatform-Ban-Regex', $e->getFailures()[0]->getHeaderName());
        $this->assertSame('(/foo)($|\\,)', $e->getFailures()[0]->getHeaderValue());
        $this->assertInstanceOf(ServerException::class, $e->getFailures()[0]->getException());
    }

    public function testEveryBanIsSentWhenAnEarlierBanFails(): void
    {
        $sent = [];
        $failing = ['(/foo/3|/foo/4)($|\\,)' => new MockResponse('', ['http_code' => 503])];
        $clientA = $this->createRecordingClient('http://varnish-a', $sent, $failing);
        $clientB = $this->createRecordingClient('http://varnish-b', $sent, $failing);

        $e = $this->catchPurgeFailure(static fn () => (new VarnishPurger([$clientA, $clientB], 25))->purge(['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6']));

        $expected = ['(/foo/1|/foo/2)($|\\,)', '(/foo/3|/foo/4)($|\\,)', '(/foo/5|/foo/6)($|\\,)'];
        $this->assertSame($expected, $sent['http://varnish-a']);
        $this->assertSame($expected, $sent['http://varnish-b']);
        $this->assertCount(2, $e->getFailures());
    }

    public function testATransportErrorIsABanFailure(): void
    {
        $sent = [];
        $client = $this->createRecordingClient('http://varnish-a', $sent, ['(/foo)($|\\,)' => new MockResponse('', ['error' => 'Connection refused'])]);

        $e = $this->catchPurgeFailure(static fn () => (new VarnishPurger([$client]))->purge(['/foo']));

        $this->assertCount(1, $e->getFailures());
        $this->assertInstanceOf(TransportException::class, $e->getFailures()[0]->getException());
    }

    public function testEveryBanFailureIsReportedWithTheFirstAsPrevious(): void
    {
        $sent = [];
        $clientA = $this->createRecordingClient('http://varnish-a', $sent, ['(/foo/1|/foo/2)($|\\,)' => new MockResponse('', ['http_code' => 500])]);
        $clientB = $this->createRecordingClient('http://varnish-b', $sent, [
            '(/foo/1|/foo/2)($|\\,)' => new MockResponse('', ['error' => 'Connection refused']),
            '(/foo/5|/foo/6)($|\\,)' => new MockResponse('', ['http_code' => 502]),
        ]);

        $e = $this->catchPurgeFailure(static fn () => (new VarnishPurger([$clientA, $clientB], 25))->purge(['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6']));

        $failures = array_map(static fn ($failure): array => [$failure->getUrl(), $failure->getHeaderValue()], $e->getFailures());
        $this->assertSame([
            ['http://varnish-a/', '(/foo/1|/foo/2)($|\\,)'],
            ['http://varnish-b/', '(/foo/1|/foo/2)($|\\,)'],
            ['http://varnish-b/', '(/foo/5|/foo/6)($|\\,)'],
        ], $failures);
        $this->assertSame($e->getFailures()[0]->getException(), $e->getPrevious());
        $this->assertSame('3 of 6 HTTP cache purge requests failed.', $e->getMessage());
    }

    public function testEveryBanReachesEveryClientWhenNothingFails(): void
    {
        $sent = [];
        $clientA = $this->createRecordingClient('http://varnish-a', $sent);
        $clientB = $this->createRecordingClient('http://varnish-b', $sent);

        (new VarnishPurger([$clientA, $clientB], 25))->purge(['/foo/1', '/foo/2', '/foo/3', '/foo/4', '/foo/5', '/foo/6']);

        $expected = ['(/foo/1|/foo/2)($|\\,)', '(/foo/3|/foo/4)($|\\,)', '(/foo/5|/foo/6)($|\\,)'];
        $this->assertSame($expected, $sent['http://varnish-a']);
        $this->assertSame($expected, $sent['http://varnish-b']);
    }
}
