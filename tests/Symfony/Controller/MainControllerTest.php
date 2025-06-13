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

namespace ApiPlatform\Tests\Symfony\Controller;

use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Controller\MainController;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MainControllerTest extends TestCase
{
    public function testControllerNotSupported(): void
    {
        $this->expectException(RuntimeException::class);
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $controller = new MainController($resourceMetadataFactory, $provider, $processor);
        $controller->__invoke(new Request());
    }

    public function testController(): void
    {
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $controller = new MainController($resourceMetadataFactory, $provider, $processor);

        $body = new \stdClass();
        $response = new Response();
        $request = new Request();
        $request->attributes->set('_api_operation', new Get());

        $provider->expects($this->once())
            ->method('provide')
            ->willReturn($body);

        $processor->expects($this->once())
            ->method('process')
            ->willReturn($response);

        $this->assertEquals($response, $controller->__invoke($request));
    }

    public function testControllerWithNonExistentUriVariables(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $controller = new MainController($resourceMetadataFactory, $provider, $processor);

        $body = new \stdClass();
        $response = new Response();
        $request = new Request();
        $request->attributes->set('_api_operation', new Get(uriVariables: ['id' => new Link()]));

        $controller->__invoke($request);
    }

    public function testControllerWithUriVariables(): void
    {
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $controller = new MainController($resourceMetadataFactory, $provider, $processor);

        $body = new \stdClass();
        $response = new Response();
        $request = new Request();
        $request->attributes->set('_api_operation', new Get(uriVariables: ['id' => new Link()]));
        $request->attributes->set('id', 0);

        $provider->expects($this->once())
            ->method('provide')
            ->willReturn($body);

        $processor->expects($this->once())
            ->method('process')
            ->willReturn($response);

        $this->assertEquals($response, $controller->__invoke($request));
    }

    public function testControllerErrorWithUriVariables(): void
    {
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $controller = new MainController($resourceMetadataFactory, $provider, $processor);

        $body = new \stdClass();
        $response = new Response();
        $request = new Request();
        $request->attributes->set('exception', new \Exception());
        $request->attributes->set('_api_operation', new Error(uriVariables: ['id' => new Link()]));

        $provider->expects($this->once())
            ->method('provide')
            ->willReturn($body);

        $processor->expects($this->once())
            ->method('process')
            ->willReturn($response);

        $this->assertEquals($response, $controller->__invoke($request));
    }

    public function testControllerErrorWithUriVariablesDuringProvider(): void
    {
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $provider = $this->createMock(ProviderInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);
        $controller = new MainController($resourceMetadataFactory, $provider, $processor, logger: $logger);

        $response = new Response();
        $request = new Request();
        $request->attributes->set('exception', new \Exception());
        $request->attributes->set('_api_operation', new Get(uriVariables: ['id' => new Link()]));
        $request->attributes->set('id', '1');

        $provider->expects($this->once())
            ->method('provide')
            ->willReturnCallback(function () use ($request) {
                $request->attributes->set('_api_operation', new Error(uriVariables: ['status' => new Link()]));
                $request->attributes->remove('id');

                return new \stdClass();
            });

        $logger->expects($this->never())->method('error');
        $processor->expects($this->once())
            ->method('process')
            ->willReturn($response);

        $this->assertEquals($response, $controller->__invoke($request));
    }
}
