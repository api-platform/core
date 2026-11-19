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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AddTagsProcessorTest extends TestCase
{
    public function testAddTags(): void
    {
        $operation = new Get();
        $response = new Response('', 200, ['Cache-Control' => 'public, max-age=10']);
        $request = new Request(attributes: ['_resources' => ['a', 'b']]);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getIriFromResource');
        $processor = new AddTagsProcessor($decorated, $iriConverter);
        $processor->process($response, $operation, [], $context);

        $this->assertSame('a,b', $response->headers->get('Cache-Tags'));
    }

    public function testAddTagsCollection(): void
    {
        $operation = new GetCollection(class: \stdClass::class, uriVariables: ['id' => new Link()]);
        $response = new Response('', 200, ['Cache-Control' => 'public, max-age=10']);
        $request = new Request(attributes: ['_resources' => ['a', 'b'], 'id' => 1]);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->once())->method('getIriFromResource')->with(\stdClass::class, UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => ['id' => 1]])->willReturn('/foos/1/bars');
        $processor = new AddTagsProcessor($decorated, $iriConverter);
        $processor->process($response, $operation, [], $context);

        $this->assertSame('a,b,/foos/1/bars', $response->headers->get('Cache-Tags'));
    }

    public function testAddTagsWithPurger(): void
    {
        $operation = new Get();
        $response = new Response('', 200, ['Cache-Control' => 'public, max-age=10']);
        $request = new Request(attributes: ['_resources' => ['a', 'b']]);
        $context = ['request' => $request];
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->method('process')->willReturn($response);
        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->expects($this->never())->method('getIriFromResource');
        $purger = $this->createMock(PurgerInterface::class);
        $purger->expects($this->once())->method('getResponseHeaders')->willReturn(['Cache-Tags' => 'a,b']);
        $processor = new AddTagsProcessor($decorated, $iriConverter, $purger);
        $processor->process($response, $operation, [], $context);

        $this->assertSame('a,b', $response->headers->get('Cache-Tags'));
    }
}
