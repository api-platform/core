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
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

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
            $stack[] = \sprintf('/tags/%d', $i);
        }

        return $stack;
    }

    public function testMultiChunkedTags(): void
    {
        $client = new class implements HttpClientInterface {
            public array $sentRegexes = [];

            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $this->sentRegexes[] = $options['headers']['Surrogate-Key'];

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
        $purger = new SouinPurger([$client]);
        $purger->purge($this->generateXResourcesTags(200));

        self::assertSame([
            implode(', ', $this->generateXResourcesTags(146)),
            implode(', ', $this->generateXResourcesTags(200, 146)),
        ], $client->sentRegexes);
    }

    public function testPurgeWithMultipleClients(): void
    {
        $client1 = new class implements HttpClientInterface {
            public array $requests = [];

            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $this->requests[] = [$method, 'http://dummy_host/dummy_api_path/souin_api', $options];

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
        $client2 = new class implements HttpClientInterface {
            public array $requests = [];

            public function request(string $method, string $url, array $options = []): ResponseInterface
            {
                $this->requests[] = [$method, 'http://dummy_host/dummy_api_path/souin_api', $options];

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

        $purger = new SouinPurger([$client1, $client2]);
        $purger->purge(['/foo']);
        self::assertSame([
            Request::METHOD_PURGE,
            'http://dummy_host/dummy_api_path/souin_api',
            ['headers' => ['Surrogate-Key' => '/foo']],
        ], $client1->requests[0]);
        self::assertSame([
            Request::METHOD_PURGE,
            'http://dummy_host/dummy_api_path/souin_api',
            ['headers' => ['Surrogate-Key' => '/foo']],
        ], $client2->requests[0]);
    }

    public function testGetResponseHeaders(): void
    {
        $purger = new SouinPurger([]);
        self::assertSame(['Surrogate-Key' => ''], $purger->getResponseHeaders([]));
        self::assertSame(['Surrogate-Key' => 'first-value/, second'], $purger->getResponseHeaders(['first-value/', 'second']));
        self::assertSame(['Surrogate-Key' => 'C0mplex_Value/, The value with spaces'], $purger->getResponseHeaders(['C0mplex_Value/', 'The value with spaces']));
    }
}
