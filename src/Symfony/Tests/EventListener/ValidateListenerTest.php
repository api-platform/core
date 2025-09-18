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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\EventListener\ValidateListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ValidateListenerTest extends TestCase
{
    public function testFetchOperation(): void
    {
        $controllerResult = new \stdClass();
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide');
        $metadata = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->expects($this->once())->method('create')->with(\stdClass::class)->willReturn(new ResourceMetadataCollection(\stdClass::class, [
            new ApiResource(operations: [
                'operation' => new Post(),
            ]),
        ]));

        $request = new Request([], [], ['_api_operation_name' => 'operation', '_api_resource_class' => \stdClass::class]);
        $listener = new ValidateListener($provider, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public function testCallprovider(): void
    {
        $controllerResult = new \stdClass();
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide');
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $request = new Request([], [], ['_api_operation' => new Post(), '_api_operation_name' => 'operation', '_api_resource_class' => \stdClass::class]);
        $listener = new ValidateListener($provider, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public function testCallproviderContext(): void
    {
        $operation = new Post(class: \stdClass::class);
        $controllerResult = new \stdClass();
        $uriVariables = ['id' => 3];
        $request = new Request([], [], ['_api_operation' => $operation, '_api_operation_name' => 'operation', '_api_resource_class' => \stdClass::class, '_api_uri_variables' => $uriVariables]);
        $request->setMethod($operation->getMethod());
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')
            ->with($operation->withValidate(true), $uriVariables, ['request' => $request, 'uri_variables' => $uriVariables, 'resource_class' => \stdClass::class]);
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $listener = new ValidateListener($provider, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public function testDeleteNoValidate(): void
    {
        $operation = new Delete(class: \stdClass::class);
        $controllerResult = new \stdClass();
        $uriVariables = ['id' => 3];
        $request = new Request([], [], ['_api_operation' => $operation, '_api_operation_name' => 'operation', '_api_resource_class' => \stdClass::class, '_api_uri_variables' => $uriVariables]);
        $request->setMethod($operation->getMethod());
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')
            ->with($operation->withValidate(false), $uriVariables, ['request' => $request, 'uri_variables' => $uriVariables, 'resource_class' => \stdClass::class]);
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $listener = new ValidateListener($provider, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public function testDeleteForceValidate(): void
    {
        $operation = new Delete(class: \stdClass::class, validate: true);
        $controllerResult = new \stdClass();
        $uriVariables = ['id' => 3];
        $request = new Request([], [], ['_api_operation' => $operation, '_api_operation_name' => 'operation', '_api_resource_class' => \stdClass::class, '_api_uri_variables' => $uriVariables]);
        $request->setMethod($operation->getMethod());
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->once())->method('provide')
            ->with($operation->withValidate(true), $uriVariables, ['request' => $request, 'uri_variables' => $uriVariables, 'resource_class' => \stdClass::class]);
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $listener = new ValidateListener($provider, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    #[DataProvider('provideNonApiAttributes')]
    public function testNoCallprovider(...$attributes): void
    {
        $controllerResult = new \stdClass();
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->never())->method('provide');
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class));
        $request = new Request([], [], $attributes);
        $listener = new ValidateListener($provider, $metadata);
        $listener->onKernelView(
            new ViewEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $controllerResult
            )
        );
    }

    public static function provideNonApiAttributes(): array
    {
        return [
            ['_api_respond' => false, '_api_operation_name' => 'dummy'],
            [],
        ];
    }
}
