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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\User;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EventListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDelete()
    {
        $user = $this->prophesize(UserInterface::class);

        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'delete']);
        $request->setMethod(Request::METHOD_DELETE);

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser($user)->shouldBeCalled();

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getControllerResult()->willReturn($user)->shouldBeCalled();
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->setControllerResult(null)->shouldBeCalled();

        $listener = new EventListener($manager->reveal());
        $listener->onKernelView($event->reveal());
    }

    public function testUpdate()
    {
        $user = $this->prophesize(UserInterface::class);

        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'put']);
        $request->setMethod(Request::METHOD_PUT);

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->updateUser($user)->shouldBeCalled();

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getControllerResult()->willReturn($user)->shouldBeCalled();
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->setControllerResult()->shouldNotBeCalled();

        $listener = new EventListener($manager->reveal());
        $listener->onKernelView($event->reveal());
    }

    public function testNotApiRequest()
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

    public function testNotUser()
    {
        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'put']);
        $request->setMethod(Request::METHOD_PUT);

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser()->shouldNotBeCalled();
        $manager->updateUser()->shouldNotBeCalled();

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getControllerResult()->willReturn(new \stdClass());

        $listener = new EventListener($manager->reveal());
        $listener->onKernelView($event->reveal());
    }

    public function testSafeMethod()
    {
        $request = new Request([], [], ['_api_resource_class' => User::class, '_api_item_operation_name' => 'put']);

        $manager = $this->prophesize(UserManagerInterface::class);
        $manager->deleteUser()->shouldNotBeCalled();
        $manager->updateUser()->shouldNotBeCalled();

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getControllerResult()->willReturn(new User());

        $listener = new EventListener($manager->reveal());
        $listener->onKernelView($event->reveal());
    }
}
