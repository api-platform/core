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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Symfony\EventListener\WriteListener;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\OperationResource;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class WriteListenerTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $processorProphecy;
    private ObjectProphecy $iriConverterProphecy;
    private ObjectProphecy $resourceMetadataCollectionFactory;
    private ObjectProphecy $resourceClassResolver;

    public static function noopProcessor($data)
    {
        return $data;
    }

    protected function setUp(): void
    {
        $this->processorProphecy = $this->prophesize(ProcessorInterface::class);
        $this->iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $this->resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
    }

    public function testOnKernelViewWithControllerResultAndPersist(): void
    {
        $operationResource = new OperationResource(1, 'foo');

        $this->iriConverterProphecy->getIriFromResource($operationResource)->willReturn('/operation_resources/1')->shouldBeCalled();
        $this->resourceClassResolver->isResourceClass(Argument::type('string'))->willReturn(true);
        $this->processorProphecy->process($operationResource, Argument::type(Operation::class), [], Argument::type('array'))->willReturn($operationResource)->shouldBeCalled();

        $operationResourceMetadata = new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations([
            '_api_OperationResource_patch' => (new Patch())->withName('_api_OperationResource_patch')->withProcessor('processor'),
            '_api_OperationResource_put' => (new Put())->withName('_api_OperationResource_put'),
            '_api_OperationResource_post_collection' => (new Post())->withName('_api_OperationResource_post_collection'),
        ]))]);

        $this->resourceMetadataCollectionFactory->create(OperationResource::class)->willReturn($operationResourceMetadata);

        $request = new Request([], [], ['_api_resource_class' => OperationResource::class]);

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $operationResource
        );

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);
            $request->attributes->set('_api_operation_name', sprintf('_api_%s_%s%s', 'OperationResource', strtolower($httpMethod), 'POST' === $httpMethod ? '_collection' : ''));

            (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
            $this->assertSame($operationResource, $event->getControllerResult());
            $this->assertSame('/operation_resources/1', $request->attributes->get('_api_write_item_iri'));
        }
    }

    public function testOnKernelViewDoNotCallIriConverterWhenOutputClassDisabled(): void
    {
        $operationResource = new OperationResource(1, 'foo');

        $this->processorProphecy->process($operationResource, Argument::type(Operation::class), [], Argument::type('array'))->willReturn($operationResource)->shouldBeCalled();

        $this->iriConverterProphecy->getIriFromResource($operationResource)->shouldNotBeCalled();
        $this->resourceClassResolver->isResourceClass(Argument::type('string'))->willReturn(true);

        $operationResourceMetadata = new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations([
            'create_no_output' => (new Post())->withOutput(false)->withName('create_no_output')->withProcessor('processor'),
        ]))]);

        $this->resourceMetadataCollectionFactory->create(OperationResource::class)->willReturn($operationResourceMetadata);

        $request = new Request([], [], ['_api_resource_class' => OperationResource::class, '_api_operation_name' => 'create_no_output']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $operationResource
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithControllerResultAndRemove(): void
    {
        $operationResource = new OperationResource(1, 'foo');

        $this->processorProphecy->process($operationResource, Argument::type(Operation::class), ['identifier' => 1], Argument::type('array'))->willReturn($operationResource)->shouldBeCalled();

        $this->iriConverterProphecy->getIriFromResource($operationResource)->shouldNotBeCalled();
        $operationResourceMetadata = new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations([
            '_api_OperationResource_delete' => (new Delete())->withUriVariables(['identifier' => (new Link())->withFromClass(OperationResource::class)->withIdentifiers(['identifier'])])->withProcessor('processor')->withName('_api_OperationResource_delete'),
        ]))]);

        $this->resourceMetadataCollectionFactory->create(OperationResource::class)->willReturn($operationResourceMetadata);

        $request = new Request([], [], ['_api_resource_class' => OperationResource::class, '_api_operation_name' => '_api_OperationResource_delete', 'identifier' => 1]);
        $request->setMethod('DELETE');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $operationResource
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithSafeMethod(): void
    {
        $operationResource = new OperationResource(1, 'foo');
        $operation = (new Get())->withName('_api_OperationResource_get');

        $this->processorProphecy->process($operationResource, Argument::type(Operation::class), [], ['operation' => $operation, 'resource_class' => OperationResource::class, 'previous_data' => 'test'])->willReturn($operationResource)->shouldNotBeCalled();

        $this->iriConverterProphecy->getIriFromResource($operationResource)->shouldNotBeCalled();

        $operationResourceMetadata = new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations([
            '_api_OperationResource_get' => $operation,
        ]))]);

        $this->resourceMetadataCollectionFactory->create(OperationResource::class)->willReturn($operationResourceMetadata);

        $request = new Request([], [], ['_api_resource_class' => OperationResource::class, '_api_operation_name' => '_api_OperationResource_get', 'previous_data' => 'test']);
        $request->setMethod('GET');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $operationResource
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }

    public function testDoNotWriteWhenControllerResultIsResponse(): void
    {
        $this->processorProphecy->process(Argument::cetera())->shouldNotBeCalled();

        $request = new Request();

        $response = new Response();

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }

    public function testDoNotWriteWhenCant(): void
    {
        $operationResource = new OperationResource(1, 'foo');

        $this->processorProphecy->process(Argument::cetera())->shouldNotBeCalled();

        $operationResourceMetadata = new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations([
            'create_no_write' => (new Post())->withWrite(false),
        ]))]);

        $this->resourceMetadataCollectionFactory->create(OperationResource::class)->willReturn($operationResourceMetadata);

        $request = new Request([], [], ['_api_resource_class' => OperationResource::class, '_api_operation_name' => 'create_no_write']);
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $operationResource
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithNoResourceClass(): void
    {
        $operationResource = new OperationResource(1, 'foo');

        $this->processorProphecy->process(Argument::cetera())->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource($operationResource)->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod('POST');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $operationResource
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewInvalidIdentifiers(): void
    {
        $attributeResource = new AttributeResource(1, 'name');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Invalid identifier value or configuration.');

        $this->processorProphecy->process($attributeResource, Argument::type(Operation::class), ['slug' => 'test'], Argument::type('array'))->shouldNotBeCalled();

        $this->iriConverterProphecy->getIriFromResource($attributeResource)->shouldNotBeCalled();
        $this->resourceClassResolver->isResourceClass(Argument::type('string'))->shouldNotBeCalled();

        $operationResourceMetadata = new ResourceMetadataCollection(OperationResource::class, [(new ApiResource())->withOperations(new Operations([
            '_api_OperationResource_delete' => (new Delete())->withUriVariables(['identifier' => (new Link())->withFromClass(OperationResource::class)->withIdentifiers(['identifier'])])->withProcessor('processor')->withName('_api_OperationResource_delete'),
        ]))]);

        $this->resourceMetadataCollectionFactory->create(OperationResource::class)->willReturn($operationResourceMetadata);

        $request = new Request([], [], ['_api_resource_class' => OperationResource::class, '_api_operation_name' => '_api_OperationResource_delete', 'slug' => 'foo']);
        $request->setMethod('DELETE');

        $event = new ViewEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST,
            $attributeResource
        );

        (new WriteListener($this->processorProphecy->reveal(), $this->iriConverterProphecy->reveal(), $this->resourceClassResolver->reveal(), $this->resourceMetadataCollectionFactory->reveal()))->onKernelView($event);
    }
}
