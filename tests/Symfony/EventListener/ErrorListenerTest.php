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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\ApiResource\Error;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Symfony\EventListener\ErrorListener;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class ErrorListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDuplicateException(): void
    {
        $exception = new \Exception();
        $operation = new Get(name: '_api_errors_problem', priority: 0, status: 400);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Error::class)
                                          ->willReturn(new ResourceMetadataCollection(Error::class, [new ApiResource(operations: [$operation])]));
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass($exception::class)->willReturn(false);
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel->handle(Argument::that(function ($request) {
            $this->assertTrue($request->attributes->has('_api_original_route'));
            $this->assertTrue($request->attributes->has('_api_original_route_params'));
            $this->assertTrue($request->attributes->has('_api_requested_operation'));
            $this->assertTrue($request->attributes->has('_api_previous_operation'));
            $this->assertEquals('_api_errors_problem', $request->attributes->get('_api_operation_name'));

            return true;
        }), HttpKernelInterface::SUB_REQUEST, false)->willReturn(new Response());
        $exceptionEvent = new ExceptionEvent($kernel->reveal(), Request::create('/'), HttpKernelInterface::SUB_REQUEST, $exception);
        $errorListener = new ErrorListener('action', null, true, [], $resourceMetadataCollectionFactory->reveal(), ['jsonproblem' => ['application/problem+json']], [], null, $resourceClassResolver->reveal());
        $errorListener->onKernelException($exceptionEvent);
    }

    public function testDuplicateExceptionWithHydra(): void
    {
        $exception = new \Exception();
        $operation = new Get(name: '_api_errors_hydra', priority: 0, status: 400);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Error::class)
                                          ->willReturn(new ResourceMetadataCollection(Error::class, [new ApiResource(operations: [$operation])]));
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass($exception::class)->willReturn(false);
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel->handle(Argument::that(function ($request) {
            $this->assertTrue($request->attributes->has('_api_original_route'));
            $this->assertTrue($request->attributes->has('_api_original_route_params'));
            $this->assertTrue($request->attributes->has('_api_requested_operation'));
            $this->assertTrue($request->attributes->has('_api_previous_operation'));
            $this->assertEquals('_api_errors_hydra', $request->attributes->get('_api_operation_name'));

            return true;
        }), HttpKernelInterface::SUB_REQUEST, false)->willReturn(new Response());
        $exceptionEvent = new ExceptionEvent($kernel->reveal(), Request::create('/'), HttpKernelInterface::SUB_REQUEST, $exception);
        $errorListener = new ErrorListener('action', null, true, [], $resourceMetadataCollectionFactory->reveal(), ['jsonld' => ['application/ld+json']], [], null, $resourceClassResolver->reveal());
        $errorListener->onKernelException($exceptionEvent);
    }

    public function testDuplicateExceptionWithErrorResource(): void
    {
        $exception = Error::createFromException(new \Exception(), 400);
        $operation = new Get(name: '_api_errors_hydra', priority: 0, status: 400, outputFormats: ['jsonld' => ['application/ld+json']]);
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create(Error::class)
                                          ->willReturn(new ResourceMetadataCollection(Error::class, [new ApiResource(operations: [$operation])]));
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Error::class)->willReturn(true);
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel->handle(Argument::that(function ($request) {
            $this->assertTrue($request->attributes->has('_api_original_route'));
            $this->assertTrue($request->attributes->has('_api_original_route_params'));
            $this->assertTrue($request->attributes->has('_api_requested_operation'));
            $this->assertTrue($request->attributes->has('_api_previous_operation'));
            $this->assertEquals('_api_errors_hydra', $request->attributes->get('_api_operation_name'));
            $this->assertEquals($request->attributes->get('id'), 1);

            return true;
        }), HttpKernelInterface::SUB_REQUEST, false)->willReturn(new Response());
        $exceptionEvent = new ExceptionEvent($kernel->reveal(), Request::create('/'), HttpKernelInterface::SUB_REQUEST, $exception);
        $identifiersExtractor = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractor->getIdentifiersFromItem($exception, Argument::any())->willReturn(['id' => 1]);
        $errorListener = new ErrorListener('action', null, true, [], $resourceMetadataCollectionFactory->reveal(), ['jsonld' => ['application/ld+json']], [], $identifiersExtractor->reveal(), $resourceClassResolver->reveal());
        $errorListener->onKernelException($exceptionEvent);
    }
}
