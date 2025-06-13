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

namespace ApiPlatform\HttpCache\Tests\State;

use ApiPlatform\HttpCache\State\AddHeadersProcessor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddHeadersProcessorTest extends TestCase
{
    public function testAddHeadersFromGlobalConfiguration(): void
    {
        $operation = new Get();
        $response = new Response('content');
        $request = new Request();
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $processor = new AddHeadersProcessor($decorated, etag: true, maxAge: 100, sharedMaxAge: 200, vary: ['Accept', 'Accept-Encoding'], public: true, staleWhileRevalidate: 10, staleIfError: 10);

        $processor->process($response, $operation, [], $context);

        self::assertSame('max-age=100, public, s-maxage=200, stale-if-error=10, stale-while-revalidate=10', $response->headers->get('cache-control'));
        self::assertSame('"55f2b31a6acfaa64"', $response->headers->get('etag'));
        self::assertSame(['Accept', 'Accept-Encoding'], $response->headers->all('vary'));
    }

    public function testAddHeadersFromOperationConfiguration(): void
    {
        $operation = new Get(
            cacheHeaders: [
                'public' => false,
                'max_age' => 250,
                'shared_max_age' => 500,
                'stale_while_revalidate' => 30,
                'stale_if_error' => 15,
                'vary' => ['Authorization', 'Accept-Language'],
                'must_revalidate' => true,
                'proxy_revalidate' => true,
                'no_cache' => true,
                'no_store' => true,
                'no_transform' => true,
                'immutable' => true,
            ],
        );
        $response = new Response('content');
        $request = new Request();
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $processor = new AddHeadersProcessor($decorated);

        $processor->process($response, $operation, [], $context);

        self::assertSame('immutable, max-age=250, must-revalidate, no-store, no-transform, private, proxy-revalidate, stale-if-error=15, stale-while-revalidate=30', $response->headers->get('cache-control'));
        self::assertSame(['Authorization', 'Accept-Language'], $response->headers->all('vary'));
    }
}
