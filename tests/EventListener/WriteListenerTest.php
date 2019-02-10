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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\Event\WriteEvent;
use ApiPlatform\Core\EventListener\WriteListener;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ConcreteDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class WriteListenerTest extends TestCase
{
    public function testHandleEventWithControllerResultAndPersist()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummy/1')->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $event = new WriteEvent($dummy, ['request' => $request]);

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);
            $request->attributes->set(sprintf('_api_%s_operation_name', 'POST' === $httpMethod ? 'collection' : 'item'), strtolower($httpMethod));

            (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);
            $this->assertSame($dummy, $event->getData());
            $this->assertEquals('/dummy/1', $request->attributes->get('_api_write_item_iri'));
        }
    }

    /**
     * @group legacy
     * @expectedDeprecation Returning void from ApiPlatform\Core\DataPersister\DataPersisterInterface::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3, an object should always be returned.
     */
    public function testHandleEventWithControllerResultAndPersistReturningVoid()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $event = new WriteEvent($dummy, ['request' => $request]);

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);
            $request->attributes->set(sprintf('_api_%s_operation_name', 'POST' === $httpMethod ? 'collection' : 'item'), strtolower($httpMethod));

            (new WriteListener($dataPersisterProphecy->reveal()))->handleEvent($event);
            $this->assertSame($dummy, $event->getData());
        }
    }

    /**
     * @see https://github.com/api-platform/core/issues/1799
     */
    public function testHandleEventWithControllerResultAndPersistWithImmutableResource()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dummy2 = new Dummy();
        $dummy2->setName('Dummyferoce');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummy/1')->shouldBeCalled();

        $dataPersisterProphecy
            ->persist($dummy, Argument::type('array'))
            ->willReturn($dummy2) // Persist is not mutating $dummy, but return a brand new technically unrelated object instead
            ->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $event = new WriteEvent($dummy, ['request' => $request]);

            $request->setMethod($httpMethod);
            $request->attributes->set(sprintf('_api_%s_operation_name', 'POST' === $httpMethod ? 'collection' : 'item'), strtolower($httpMethod));

            (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);

            $this->assertSame($dummy2, $event->getData());
            $this->assertEquals('/dummy/1', $request->attributes->get('_api_write_item_iri'));
        }
    }

    public function testHandleEventDoNotCallIriConverterWhenOutputClassDisabled()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['output' => ['class' => null]]));

        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy)->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal()))->handleEvent($event);
    }

    public function testHandleEventWithControllerResultAndRemove()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->remove($dummy)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'delete']);
        $request->setMethod('DELETE');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);
    }

    public function testHandleEventWithSafeMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'head']);
        $request->setMethod('HEAD');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal()))->handleEvent($event);
    }

    public function testHandleEventWithPersistFlagOff()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_item_operation_name' => 'head', '_api_persist' => false]);
        $request->setMethod('HEAD');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);
    }

    public function testHandleViewWithNoResourceClass()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod('POST');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);
    }

    public function testHandleViewWithParentResourceClass()
    {
        $dummy = new ConcreteDummy();

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->willReturn($dummy)->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->willReturn('/dummy/1')->shouldBeCalled();

        $request = new Request([], [], ['_api_resource_class' => ConcreteDummy::class, '_api_item_operation_name' => 'put', '_api_persist' => true]);
        $request->setMethod('PUT');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);
    }

    public function testHandleViewWithNoDataPersisterSupport()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(false)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromItem($dummy)->shouldNotBeCalled();

        $request = new Request([], [], ['_api_resource_class' => 'Dummy', '_api_collection_operation_name' => 'post']);
        $request->setMethod('POST');

        $event = new WriteEvent($dummy, ['request' => $request]);

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->handleEvent($event);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\EventListener\WriteListener::onKernelView() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent" as argument of "ApiPlatform\Core\EventListener\WriteListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelView()
    {
        $dummy = new Dummy();

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->willReturn(false);

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal(), $iriConverterProphecy->reveal()))->onKernelView($event);
    }
}
