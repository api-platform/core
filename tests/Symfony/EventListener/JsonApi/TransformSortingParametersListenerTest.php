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

namespace ApiPlatform\Tests\Symfony\EventListener\JsonApi;

use ApiPlatform\Symfony\EventListener\JsonApi\TransformSortingParametersListener;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class TransformSortingParametersListenerTest extends TestCase
{
    use ProphecyTrait;

    private TransformSortingParametersListener $listener;

    protected function setUp(): void
    {
        $this->listener = new TransformSortingParametersListener();
    }

    public function testOnKernelRequestWithInvalidFormat(): void
    {
        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('badformat');

        $request = $expectedRequest->duplicate();

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequestWithInvalidFilter(): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);

        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('jsonapi');

        $request = $expectedRequest->duplicate();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);

        $expectedRequest = $expectedRequest->duplicate(['sort' => ['foo', '-bar']]);

        $request = $expectedRequest->duplicate();
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $this->listener->onKernelRequest($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testOnKernelRequest(): void
    {
        $request = new Request(['sort' => 'foo,-bar,-baz,qux']);
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());

        $expectedRequest = new Request(['sort' => 'foo,-bar,-baz,qux'], [], ['_api_filters' => ['order' => ['foo' => 'asc', 'bar' => 'desc', 'baz' => 'desc', 'qux' => 'asc']]]);
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }
}
