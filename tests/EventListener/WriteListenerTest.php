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
        $dataPersisterProphecy->persist($dummy)->shouldBeCalled();

        $request = new Request();
        $request->attributes->set('_api_resource_class', Dummy::class);

        $event = new GetResponseForControllerResultEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $dummy
        );

        foreach ([Request::METHOD_PATCH, Request::METHOD_PUT, Request::METHOD_POST] as $httpMethod) {
            $request->setMethod($httpMethod);

            (new WriteListener($dataPersisterProphecy->reveal()))->onKernelView($event);
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
        $request->setMethod(Request::METHOD_DELETE);
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
        $request->setMethod(Request::METHOD_HEAD);
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
        $request->setMethod(Request::METHOD_POST);

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
        $request->setMethod(Request::METHOD_POST);
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
