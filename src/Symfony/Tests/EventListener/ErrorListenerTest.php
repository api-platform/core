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

namespace ApiPlatform\Symfony\Tests\EventListener;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\Symfony\EventListener\ErrorListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ErrorListenerTest extends TestCase
{
    public function testDuplicateException(): void
    {
        $exception = new \Exception();
        $operation = new Get(name: '_api_errors_problem', priority: 0, status: 400);
        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())->method('create')
                                                                  ->with(Error::class)
                                                                  ->willReturn(
                                                                      new ResourceMetadataCollection(Error::class, [new ApiResource(operations: [$operation])])
                                                                  );

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->expects($this->once())->method('isResourceClass')->with($exception::class)->willReturn(false);
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('handle')->willReturnCallback(function ($request) {
            $this->assertTrue($request->attributes->has('_api_original_route'));
            $this->assertTrue($request->attributes->has('_api_original_route_params'));
            $this->assertTrue($request->attributes->has('_api_requested_operation'));
            $this->assertTrue($request->attributes->has('_api_previous_operation'));
            $this->assertEquals('_api_errors_problem', $request->attributes->get('_api_operation_name'));

            return new Response();
        });

        $request = Request::create('/');
        $request->attributes->set('_api_operation', new Get(extraProperties: ['rfc_7807_compliant_errors' => true]));
        $exceptionEvent = new ExceptionEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);
        $errorListener = new ErrorListener('action', null, true, [], $resourceMetadataCollectionFactory, ['jsonproblem' => ['application/problem+json']], [], null, $resourceClassResolver, problemCompliantErrors: true);
        $errorListener->onKernelException($exceptionEvent);
    }

    public function testDuplicateExceptionWithHydra(): void
    {
        $exception = new \Exception();
        $operation = new Get(name: '_api_errors_hydra', priority: 0, status: 400);
        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())->method('create')
                                                                  ->with(Error::class)
                                                                  ->willReturn(
                                                                      new ResourceMetadataCollection(Error::class, [new ApiResource(operations: [$operation])])
                                                                  );
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->expects($this->once())->method('isResourceClass')->with($exception::class)->willReturn(false);

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('handle')->willReturnCallback(function ($request) {
            $this->assertTrue($request->attributes->has('_api_original_route'));
            $this->assertTrue($request->attributes->has('_api_original_route_params'));
            $this->assertTrue($request->attributes->has('_api_requested_operation'));
            $this->assertTrue($request->attributes->has('_api_previous_operation'));
            $this->assertEquals('_api_errors_hydra', $request->attributes->get('_api_operation_name'));

            return new Response();
        });
        $request = Request::create('/');
        $request->attributes->set('_api_operation', new Get(extraProperties: ['rfc_7807_compliant_errors' => true]));
        $exceptionEvent = new ExceptionEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);
        $errorListener = new ErrorListener('action', null, true, [], $resourceMetadataCollectionFactory, ['jsonld' => ['application/ld+json']], [], null, $resourceClassResolver);
        $errorListener->onKernelException($exceptionEvent);
    }

    public function testDuplicateExceptionWithErrorResource(): void
    {
        $exception = Error::createFromException(new \Exception(), 400);
        $operation = new Get(name: '_api_errors_hydra', priority: 0, status: 400, outputFormats: ['jsonld' => ['application/ld+json']]);
        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())->method('create')
                                                                  ->with(Error::class)
                                                                  ->willReturn(
                                                                      new ResourceMetadataCollection(Error::class, [new ApiResource(operations: [$operation])])
                                                                  );

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->expects($this->once())->method('isResourceClass')->with(Error::class)->willReturn(true);

        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('handle')->willReturnCallback(function ($request) {
            $this->assertTrue($request->attributes->has('_api_original_route'));
            $this->assertTrue($request->attributes->has('_api_original_route_params'));
            $this->assertTrue($request->attributes->has('_api_requested_operation'));
            $this->assertTrue($request->attributes->has('_api_previous_operation'));
            $this->assertEquals('_api_errors_hydra', $request->attributes->get('_api_operation_name'));

            $operation = $request->attributes->get('_api_operation');
            $this->assertEquals($operation->getNormalizationContext(), [
                // this flag is for bc layer on error normalizers
                'api_error_resource' => true,
                'ignored_attributes' => [
                    'trace',
                    'file',
                    'line',
                    'code',
                    'message',
                    'traceAsString',
                ],
            ]);

            return new Response();
        });
        $request = Request::create('/');
        $request->attributes->set('_api_operation', new Get(extraProperties: ['rfc_7807_compliant_errors' => true]));
        $exceptionEvent = new ExceptionEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);
        $identifiersExtractor = $this->createStub(IdentifiersExtractorInterface::class);
        $identifiersExtractor->method('getIdentifiersFromItem')->willReturn(['id' => 1]);
        $errorListener = new ErrorListener('action', null, true, [], $resourceMetadataCollectionFactory, ['jsonld' => ['application/ld+json']], [], $identifiersExtractor, $resourceClassResolver);
        $errorListener->onKernelException($exceptionEvent);
    }
}
