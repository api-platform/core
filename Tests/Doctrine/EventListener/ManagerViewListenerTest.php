<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Tests\Doctrine\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Dunglas\ApiBundle\Doctrine\EventListener\ManagerViewListener;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class ManagerViewListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnKernelViewException()
    {
        $managerRegistryMock = $this->getManagerRegistryMock();
        $eventDispatcherMock = $this->getEventDispatcherMock();
        $eventMock = $this->getEventMock();
        $requestMock = $this->getRequestMock();

        $eventMock->getRequest()->willReturn($requestMock->reveal())->shouldBeCalled();
        $requestMock->getMethod()->willReturn(Request::METHOD_POST)->shouldBeCalled();

        $listener = new ManagerViewListener($managerRegistryMock->reveal(), $eventDispatcherMock->reveal());
        $listener->onKernelView($eventMock->reveal());
    }

    /**
     * @return ObjectProphecy|ManagerRegistry
     */
    private function getManagerRegistryMock()
    {
        return $this->prophesize('Doctrine\Common\Persistence\ManagerRegistry');
    }

    /**
     * @return ObjectProphecy|EventDispatcher
     */
    private function getEventDispatcherMock()
    {
        return $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcher');
    }

    /**
     * @return ObjectProphecy|GetResponseForControllerResultEvent
     */
    private function getEventMock()
    {
        return $this->prophesize('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent');
    }

    /**
     * @return ObjectProphecy|Request
     */
    private function getRequestMock()
    {
        return $this->prophesize('Symfony\Component\HttpFoundation\Request');
    }
}
