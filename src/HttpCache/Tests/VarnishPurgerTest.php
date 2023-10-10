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

use ApiPlatform\HttpCache\VarnishPurger;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class VarnishPurgerTest extends TestCase
{
    use ProphecyTrait;

    public function testPurge(): void
    {
        $clientProphecy1 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)']])->shouldBeCalled();
        $clientProphecy1->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo|/bar)($|\,)']])->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)']])->shouldBeCalled();
        $clientProphecy2->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo|/bar)($|\,)']])->shouldBeCalled();

        $clientProphecy3 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy3->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)']])->shouldBeCalled();
        $clientProphecy3->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/bar)($|\,)']])->shouldBeCalled();

        $clientProphecy4 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy4->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)']])->shouldBeCalled();
        $clientProphecy4->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/bar)($|\,)']])->shouldBeCalled();

        $purger = new VarnishPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);

        $purger = new VarnishPurger([$clientProphecy3->reveal(), $clientProphecy4->reveal()], 12);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    public function testEmptyTags(): void
    {
        $clientProphecy1 = $this->prophesize(ClientInterface::class);
        $clientProphecy1->request()->shouldNotBeCalled();

        /** @var HttpClientInterface $client */
        $client = $clientProphecy1->reveal();
        $purger = new VarnishPurger([$client]);
        $purger->purge([]);
    }

    /**
     * @dataProvider provideChunkHeaderCases
     */
    public function testItChunksHeaderToAvoidHittingVarnishLimit(int $maxHeaderLength, array $iris, array $regexesToSend): void
    {
        /** @var HttpClientInterface $client */
        $client = new class() implements ClientInterface {
            public array $sentRegexes = [];

            public function send(RequestInterface $request, array $options = []): ResponseInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function request($method, $uri, array $options = []): ResponseInterface
            {
                $this->sentRegexes[] = $options['headers']['ApiPlatform-Ban-Regex'];

                return new Response();
            }

            public function requestAsync($method, $uri, array $options = []): PromiseInterface
            {
                throw new \LogicException('Not implemented');
            }

            public function getConfig($option = null): void
            {
                throw new \LogicException('Not implemented');
            }
        };

        $purger = new VarnishPurger([$client], $maxHeaderLength);
        $purger->purge($iris);

        self::assertSame($regexesToSend, $client->sentRegexes); // @phpstan-ignore-line
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
                sprintf('(%s)($|\,)', implode('|', array_fill(0, 1598, '/foo'))),
                sprintf('(%s)($|\,)', implode('|', array_fill(0, 1402, '/foo'))),
            ],
        ];
    }

    public function testConstructor(): void
    {
        $clientProphecy = $this->prophesize(HttpClientInterface::class);
        $clientProphecy->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => '(/foo)($|\,)']])->shouldBeCalled();
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
}
