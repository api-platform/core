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

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\HttpCache\State\AddTagsProcessor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class AddTagsProcessorTest extends TestCase
{
    public function testAddTags(): void
    {
        $operation = new Get();
        $response = $this->createMock(Response::class);
        $response->method('isCacheable')->willReturn(true);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers->expects($this->once())->method('set')->with('Cache-Tags', 'a,b');
        $request = $this->createMock(Request::class);
        $request->method('isMethodCacheable')->willReturn(true);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('get')->with('_resources', [])->willReturn(['a', 'b']);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getIriFromResource');
        $processor = new AddTagsProcessor($decorated, $iriConverter);
        $processor->process($response, $operation, [], $context);
    }

    public function testAddTagsCollection(): void
    {
        $operation = new GetCollection(class: 'Foo', uriVariables: ['id' => new Link()]);
        $response = $this->createMock(Response::class);
        $response->method('isCacheable')->willReturn(true);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers->expects($this->once())->method('set')->with('Cache-Tags', 'a,b,/foos/1/bars');
        $request = $this->createMock(Request::class);
        $request->method('isMethodCacheable')->willReturn(true);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('get')->with('_resources', [])->willReturn(['a', 'b']);
        $request->attributes->method('all')->willReturn(['id' => 1]);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getIriFromResource')->with('Foo', UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => ['id' => 1]])->willReturn('/foos/1/bars');
        $processor = new AddTagsProcessor($decorated, $iriConverter);
        $processor->process($response, $operation, [], $context);
    }

    public function testAddTagsWithPurger(): void
    {
        $operation = new Get();
        $response = $this->createMock(Response::class);
        $response->method('isCacheable')->willReturn(true);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers->expects($this->once())->method('set')->with('Cache-Tags', 'a,b');
        $request = $this->createMock(Request::class);
        $request->method('isMethodCacheable')->willReturn(true);
        $request->attributes = $this->createMock(ParameterBag::class);
        $request->attributes->method('get')->with('_resources', [])->willReturn(['a', 'b']);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getIriFromResource');
        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('getResponseHeaders')->willReturn(['Cache-Tags' => 'a,b']);
        $processor = new AddTagsProcessor($decorated, $iriConverter, $purger);
        $processor->process($response, $operation, [], $context);
    }
}
