<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class WriteListenerTest.
 *
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
        $request->setMethod('POST');
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
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod('DELETE');
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = new GetResponseForControllerResultEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $this->assertNull($writeListener->onKernelView($event));
        $this->assertNotEquals($dummy, $writeListener->onKernelView($event));
    }

    public function testOnKernelViewWithSafeMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod('HEAD');
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
        $request->setMethod('POST');
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
        $request->setMethod('DELETE');
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = new GetResponseForControllerResultEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $this->assertNotNull($writeListener->onKernelView($event));
        $this->assertEquals($dummy, $writeListener->onKernelView($event));
    }
}
