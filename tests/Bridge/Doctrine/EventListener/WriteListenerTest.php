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

namespace ApiPlatform\Core\Tests\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class WriteListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnKernelViewWithControllerResultAndPostMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->persist($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn($objectManagerProphecy->reveal());

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = new GetResponseForControllerResultEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $this->assertNull($writeListener->onKernelView($event));
        $this->assertNotEquals($dummy, $writeListener->onKernelView($event));
    }

    public function testOnKernelViewWithControllerResultAndDeleteMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->remove($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn($objectManagerProphecy->reveal());

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $request = new Request();
        $request->setMethod(Request::METHOD_DELETE);
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->setControllerResult(null)->shouldBeCalled();
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($dummy);
        $this->assertNull($writeListener->onKernelView($event->reveal()));
        $this->assertNotEquals($dummy, $writeListener->onKernelView($event->reveal()));
    }

    public function testOnKernelViewWithSafeMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_HEAD);
        $event = new GetResponseForControllerResultEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $this->assertNull($writeListener->onKernelView($event));
        $this->assertNotEquals($dummy, $writeListener->onKernelView($event));
    }

    public function testOnKernelViewWithNoResourceClass()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $event = new GetResponseForControllerResultEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $this->assertNull($writeListener->onKernelView($event));
        $this->assertNotEquals($dummy, $writeListener->onKernelView($event));
    }

    public function testOnKernelViewWithNoManager()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn(null);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod(Request::METHOD_DELETE);
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = new GetResponseForControllerResultEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $this->assertNull($writeListener->onKernelView($event));
        $this->assertNotEquals($dummy, $writeListener->onKernelView($event));
    }
}
