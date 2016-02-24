<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine;

use Doctrine\Common\Persistence\ObjectManager;
use Dunglas\ApiBundle\Doctrine\EventSubscriber;
use Dunglas\ApiBundle\Event\Events;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $eventSubscriber = new EventSubscriber(
            $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface')->reveal(),
            $this->prophesize('Doctrine\Common\Persistence\ManagerRegistry')->reveal()
        );

        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', $eventSubscriber);
    }

    public function testGetSubscribedEvents()
    {
        $eventSubscriber = new EventSubscriber(
            $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface')->reveal(),
            $this->prophesize('Doctrine\Common\Persistence\ManagerRegistry')->reveal()
        );

        $this->assertEquals([
            Events::PRE_CREATE => ['persistObject', 0],
            Events::PRE_UPDATE => ['updateObject', 0],
            Events::PRE_DELETE => ['deleteObject', 0],
        ], $eventSubscriber->getSubscribedEvents());
    }

    public function testPersistObject()
    {
        $data = new \stdClass();
        $event = $this->getEventProphecy($data)->reveal();

        $objectManagerProphecy = $this->prophesize('Doctrine\Common\Persistence\ObjectManager');
        $objectManagerProphecy->persist($data)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManager = $objectManagerProphecy->reveal();

        $eventDispatcherProphecy = $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $eventDispatcherProphecy->dispatch(Events::POST_CREATE, $event)->shouldBeCalled();
        $eventDispatcher = $eventDispatcherProphecy->reveal();

        $eventSubscriber = new EventSubscriber($eventDispatcher, $this->getManagerRegistryProphecy($objectManager)->reveal());
        $eventSubscriber->persistObject($event);
    }

    public function testUpdateObject()
    {
        $data = new \stdClass();
        $event = $this->getEventProphecy($data)->reveal();

        $objectManagerProphecy = $this->prophesize('Doctrine\Common\Persistence\ObjectManager');
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManager = $objectManagerProphecy->reveal();

        $eventDispatcherProphecy = $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $eventDispatcherProphecy->dispatch(Events::POST_UPDATE, $event)->shouldBeCalled();
        $eventDispatcher = $eventDispatcherProphecy->reveal();

        $eventSubscriber = new EventSubscriber($eventDispatcher, $this->getManagerRegistryProphecy($objectManager)->reveal());
        $eventSubscriber->updateObject($event);
    }

    public function testDeleteObject()
    {
        $data = new \stdClass();
        $event = $this->getEventProphecy($data)->reveal();

        $objectManagerProphecy = $this->prophesize('Doctrine\Common\Persistence\ObjectManager');
        $objectManagerProphecy->remove($data)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManager = $objectManagerProphecy->reveal();

        $eventDispatcherProphecy = $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $eventDispatcherProphecy->dispatch(Events::POST_DELETE, $event)->shouldBeCalled();
        $eventDispatcher = $eventDispatcherProphecy->reveal();

        $eventSubscriber = new EventSubscriber($eventDispatcher, $this->getManagerRegistryProphecy($objectManager)->reveal());
        $eventSubscriber->deleteObject($event);
    }

    private function getEventProphecy($data)
    {
        $resourceProphecy = $this->prophesize('Dunglas\ApiBundle\Api\ResourceInterface');
        $resourceProphecy->getEntityClass()->willReturn('Foo')->shouldBeCalled();
        $resource = $resourceProphecy->reveal();

        $eventProphecy = $this->prophesize('Dunglas\ApiBundle\Event\DataEvent');
        $eventProphecy->getResource()->willReturn($resource)->shouldBeCalled();
        $eventProphecy->getData()->willReturn($data)->shouldBeCalled();
        $eventProphecy->stopPropagation()->shouldBeCalled();

        return $eventProphecy;
    }

    private function getManagerRegistryProphecy(ObjectManager $objectManager)
    {
        $managerRegistryProphecy = $this->prophesize('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistryProphecy->getManagerForClass('Foo')->willReturn($objectManager)->shouldBeCalled();

        return $managerRegistryProphecy;
    }
}
