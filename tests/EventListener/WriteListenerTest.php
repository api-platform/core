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

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\EventListener\WriteListener;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class WriteListenerTest extends TestCase
{
    public function testOnKernelViewWithControllerResultAndPersist()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy)->willReturn($dummy)->shouldBeCalled();

        $request = new Request();
        $request->attributes->set('_api_resource_class', Dummy::class);

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);

            (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
            $this->assertSame($dummy, $event->getControllerResult());
        }
    }

    /**
     * @group legacy
     * @expectedDeprecation Returning void from ApiPlatform\Core\DataPersister\DataPersisterInterface::persist() is deprecated since API Platform 2.3 and will not be supported in API Platform 3, an object should always be returned.
     */
    public function testOnKernelViewWithControllerResultAndPersistReturningVoid()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy)->shouldBeCalled();

        $request = new Request();
        $request->attributes->set('_api_resource_class', Dummy::class);

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $request->setMethod($httpMethod);

            (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
            $this->assertSame($dummy, $event->getControllerResult());
        }
    }

    /**
     * @see https://github.com/api-platform/core/issues/1799
     */
    public function testOnKernelViewWithControllerResultAndPersistWithImmutableResource()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dummy2 = new Dummy();
        $dummy2->setName('Dummyferoce');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();

        $dataPersisterProphecy
            ->persist($dummy)
            ->willReturn($dummy2) // Persist is not mutating $dummy, but return a brand new technically unrelated object instead
            ->shouldBeCalled()
        ;

        $request = new Request();
        $request->attributes->set('_api_resource_class', Dummy::class);

        foreach (['PATCH', 'PUT', 'POST'] as $httpMethod) {
            $event = new GetResponseForControllerResultEvent(
                $this->prophesize(HttpKernelInterface::class)->reveal(),
                $request,
                HttpKernelInterface::MASTER_REQUEST,
                $dummy
            );

            $request->setMethod($httpMethod);

            (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);

            $this->assertSame($dummy2, $event->getControllerResult());
        }
    }

    public function testOnKernelViewWithControllerResultAndRemove()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();
        $dataPersisterProphecy->remove($dummy)->shouldBeCalled();

        $request = new Request();
        $request->setMethod('DELETE');
        $request->attributes->set('_api_resource_class', Dummy::class);

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithSafeMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy)->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy)->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod('HEAD');
        $request->attributes->set('_api_resource_class', Dummy::class);

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithNoResourceClass()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->shouldNotBeCalled();
        $dataPersisterProphecy->persist($dummy)->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy)->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod('POST');

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
    }

    public function testOnKernelViewWithNoDataPersisterSupport()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $dataPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $dataPersisterProphecy->supports($dummy)->willReturn(false)->shouldBeCalled();
        $dataPersisterProphecy->persist($dummy)->shouldNotBeCalled();
        $dataPersisterProphecy->remove($dummy)->shouldNotBeCalled();

        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('_api_resource_class', 'Dummy');

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
    }
}
