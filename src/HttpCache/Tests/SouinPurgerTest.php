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

use ApiPlatform\HttpCache\SouinPurger;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Sylvain Combraque <darkweak@protonmail.com>
 */
class SouinPurgerTest extends TestCase
{
    use ProphecyTrait;

    public function testPurge(): void
    {
        $clientProphecy1 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy1->request('PURGE', '', ['headers' => ['Surrogate-Key' => '/foo']])->shouldBeCalled();
        $clientProphecy1->request('PURGE', '', ['headers' => ['Surrogate-Key' => '/foo, /bar']])->shouldBeCalled();

        $clientProphecy2 = $this->prophesize(HttpClientInterface::class);
        $clientProphecy2->request('PURGE', '', ['headers' => ['Surrogate-Key' => '/foo']])->shouldBeCalled();
        $clientProphecy2->request('PURGE', '', ['headers' => ['Surrogate-Key' => '/foo, /bar']])->shouldBeCalled();

        $purger = new SouinPurger([$clientProphecy1->reveal(), $clientProphecy2->reveal()]);
        $purger->purge(['/foo']);
        $purger->purge(['/foo' => '/foo', '/bar' => '/bar']);
    }

    private function generateXResourcesTags(int $number, int $minimum = 0): array
    {
        $stack = [];

        for ($i = $minimum; $i < $number; ++$i) {
            $stack[] = sprintf('/tags/%d', $i);
        }

        return $stack;
    }

    public function testMultiChunkedTags(): void
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
                $this->sentRegexes[] = $options['headers']['Surrogate-Key'];

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
        $purger = new SouinPurger([$client]);
        $purger->purge($this->generateXResourcesTags(200));

        self::assertSame([
            implode(', ', $this->generateXResourcesTags(146)),
            implode(', ', $this->generateXResourcesTags(200, 146)),
        ], $client->sentRegexes); // @phpstan-ignore-line
    }

    public function testPurgeWithMultipleClients(): void
    {
        /** @var HttpClientInterface $client1 */
        $client1 = new class() implements ClientInterface {
            public $requests = [];

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
                $this->requests[] = [$method, 'http://dummy_host/dummy_api_path/souin_api', $options];

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
        /** @var HttpClientInterface $client2 */
        $client2 = new class() implements ClientInterface {
            public $requests = [];

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
                $this->requests[] = [$method, 'http://dummy_host/dummy_api_path/souin_api', $options];

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

        $purger = new SouinPurger([$client1, $client2]);
        $purger->purge(['/foo']);
        self::assertSame([
            Request::METHOD_PURGE,
            'http://dummy_host/dummy_api_path/souin_api',
            ['headers' => ['Surrogate-Key' => '/foo']],
        ], $client1->requests[0]); // @phpstan-ignore-line
        self::assertSame([
            Request::METHOD_PURGE,
            'http://dummy_host/dummy_api_path/souin_api',
            ['headers' => ['Surrogate-Key' => '/foo']],
        ], $client2->requests[0]); // @phpstan-ignore-line
    }

    public function testGetResponseHeaders(): void
    {
        $purger = new SouinPurger([]);
        self::assertSame(['Surrogate-Key' => ''], $purger->getResponseHeaders([]));
        self::assertSame(['Surrogate-Key' => 'first-value/, second'], $purger->getResponseHeaders(['first-value/', 'second']));
        self::assertSame(['Surrogate-Key' => 'C0mplex_Value/, The value with spaces'], $purger->getResponseHeaders(['C0mplex_Value/', 'The value with spaces']));
    }
}
