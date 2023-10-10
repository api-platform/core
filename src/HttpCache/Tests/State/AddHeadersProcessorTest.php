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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AddHeadersProcessorTest extends TestCase
{
    public function testAddHeaders(): void
    {
        $operation = new Get();
        $response = $this->createMock(Response::class);
        $response->expects($this->once())->method('setEtag');
        $response->method('getContent')->willReturn('{}');
        $response->method('isSuccessful')->willReturn(true);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers->method('hasCacheControlDirective')->with($this->logicalOr(
            $this->identicalTo('public'),
            $this->identicalTo('s-maxage'),
            $this->identicalTo('max-age'),
            $this->identicalTo('stale-while-revalidate'),
            $this->identicalTo('stale-if-error'),
        ))->willReturn(false);
        $response->headers->expects($this->exactly(2))->method('addCacheControlDirective')->with($this->logicalOr(
            $this->identicalTo('stale-while-revalidate'),
            $this->identicalTo('stale-if-error'),
        ), '10');
        $response->expects($this->once())->method('setPublic');
        $response->expects($this->once())->method('setMaxAge');
        $response->expects($this->once())->method('setSharedMaxAge');
        $request = $this->createMock(Request::class);
        $request->method('isMethodCacheable')->willReturn(true);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $processor = new AddHeadersProcessor($decorated, etag: true, maxAge: 100, sharedMaxAge: 200, vary: ['Accept', 'Accept-Encoding'], public: true, staleWhileRevalidate: 10, staleIfError: 10);
        $processor->process($response, $operation, [], $context);
    }
}
