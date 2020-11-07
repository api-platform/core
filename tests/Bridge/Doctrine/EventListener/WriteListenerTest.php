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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\EventListener;

use ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @group legacy
 */
class WriteListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener class is deprecated since version 2.2 and will be removed in 3.0. Use the ApiPlatform\Core\EventListener\WriteListener class instead.
     */
    public function testOnKernelViewWithControllerResultAndPostMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->persist($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn($objectManagerProphecy);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod('POST');
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = new ViewEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $writeListener->onKernelView($event);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener class is deprecated since version 2.2 and will be removed in 3.0. Use the ApiPlatform\Core\EventListener\WriteListener class instead.
     */
    public function testOnKernelViewWithControllerResultAndDeleteMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->remove($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn($objectManagerProphecy);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $request = new Request();
        $request->setMethod('DELETE');
        $request->attributes->set('_api_resource_class', 'Dummy');

        $event = new ViewEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);
        $writeListener->onKernelView($event);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener class is deprecated since version 2.2 and will be removed in 3.0. Use the ApiPlatform\Core\EventListener\WriteListener class instead.
     */
    public function testOnKernelViewWithSafeMethod()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->flush()->shouldNotBeCalled();
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn($objectManagerProphecy);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod('HEAD');
        $request->attributes->set('_api_resource_class', 'Dummy');
        $event = new ViewEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $writeListener->onKernelView($event);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener class is deprecated since version 2.2 and will be removed in 3.0. Use the ApiPlatform\Core\EventListener\WriteListener class instead.
     */
    public function testOnKernelViewWithNoResourceClass()
    {
        $dummy = new Dummy();
        $dummy->setName('Dummyrino');

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->flush()->shouldNotBeCalled();
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass('Dummy')->willReturn($objectManagerProphecy);

        $writeListener = new WriteListener($managerRegistryProphecy->reveal());
        $httpKernelProphecy = $this->prophesize(HttpKernelInterface::class);
        $request = new Request();
        $request->setMethod('POST');
        $event = new ViewEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $writeListener->onKernelView($event);
    }

    /**
     * @expectedDeprecation The ApiPlatform\Core\Bridge\Doctrine\EventListener\WriteListener class is deprecated since version 2.2 and will be removed in 3.0. Use the ApiPlatform\Core\EventListener\WriteListener class instead.
     * @doesNotPerformAssertions
     */
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
        $event = new ViewEvent($httpKernelProphecy->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $dummy);

        $writeListener->onKernelView($event);
    }
}
