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

namespace ApiPlatform\Core\Tests\Bridge\FosUser;

use ApiPlatform\Core\Bridge\FosUser\EventListener;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EventListenerTest extends TestCase
{
    public function testDelete()
    {
        $user = $this->prophesize(UserInterface::class);

        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'delete']);
        $request->setMethod('DELETE');

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser($user)->shouldBeCalled();

        $event = $this->prophesize(EventInterface::class);
        $event->getData()->willReturn($user)->shouldBeCalled();
        $event->getContext()->willReturn(['request' => $request])->shouldBeCalled();
        $event->setData(null)->shouldBeCalled();

        $listener = new EventListener($manager->reveal());
        $listener->handleEvent($event->reveal());
    }

    public function testUpdate()
    {
        $user = $this->prophesize(UserInterface::class);

        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'put']);
        $request->setMethod('PUT');

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->updateUser($user)->shouldBeCalled();

        $event = $this->prophesize(EventInterface::class);
        $event->getData()->willReturn($user)->shouldBeCalled();
        $event->getContext()->willReturn(['request' => $request])->shouldBeCalled();
        $event->setData()->shouldNotBeCalled();

        $listener = new EventListener($manager->reveal());
        $listener->handleEvent($event->reveal());
    }

    public function testNotApiRequest()
    {
        $request = new Request();

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser()->shouldNotBeCalled();
        $manager->updateUser()->shouldNotBeCalled();

        $event = $this->prophesize(EventInterface::class);
        $event->getContext()->willReturn(['request' => $request])->shouldBeCalled();

        $listener = new EventListener($manager->reveal());
        $listener->handleEvent($event->reveal());
    }

    public function testNotUser()
    {
        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'put']);
        $request->setMethod('PUT');

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser()->shouldNotBeCalled();
        $manager->updateUser()->shouldNotBeCalled();

        $event = $this->prophesize(EventInterface::class);
        $event->getContext()->willReturn(['request' => $request])->shouldBeCalled();
        $event->getData()->willReturn(new \stdClass());

        $listener = new EventListener($manager->reveal());
        $listener->handleEvent($event->reveal());
    }

    public function testSafeMethod()
    {
        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'put']);

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser()->shouldNotBeCalled();
        $manager->updateUser()->shouldNotBeCalled();

        $event = $this->prophesize(EventInterface::class);
        $event->getContext()->willReturn(['request' => $request])->shouldBeCalled();
        $event->getData()->willReturn(new User());

        $listener = new EventListener($manager->reveal());
        $listener->handleEvent($event->reveal());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\Bridge\FosUser\EventListener::onKernelView() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent" as argument of "ApiPlatform\Core\Bridge\FosUser\EventListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelView()
    {
        $request = new Request();

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser()->shouldNotBeCalled();
        $manager->updateUser()->shouldNotBeCalled();

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new EventListener($manager->reveal());
        $listener->onKernelView($event->reveal());
    }
}
