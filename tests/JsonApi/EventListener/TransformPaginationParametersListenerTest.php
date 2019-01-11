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

namespace ApiPlatform\Core\Tests\JsonApi\EventListener;

use ApiPlatform\Core\JsonApi\EventListener\TransformPaginationParametersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class TransformPaginationParametersListenerTest extends TestCase
{
    private $listener;

    protected function setUp()
    {
        $this->listener = new TransformPaginationParametersListener();
    }

    public function testOnKernelRequestWithInvalidFormat()
    {
        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('badformat');

        $request = $expectedRequest->duplicate();

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequestWithInvalidPage()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);

        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('jsonapi');

        $request = $expectedRequest->duplicate();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);

        $expectedRequest = $expectedRequest->duplicate(['page' => 'foo']);

        $request = $expectedRequest->duplicate();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequest()
    {
        $request = new Request(['page' => ['size' => 5, 'number' => 3, 'error' => -1]]);
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $filters = ['size' => 5, 'number' => 3, 'error' => -1];

        $expectedRequest = new Request(['page' => $filters], [], ['_api_pagination' => $filters, '_api_filters' => $filters]);
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }
}
