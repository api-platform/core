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

use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\JsonApi\EventListener\TransformPaginationParametersListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class TransformPaginationParametersListenerTest extends TestCase
{
    /** @var TransformPaginationParametersListener */
    private $listener;

    protected function setUp()
    {
        $this->listener = new TransformPaginationParametersListener();
    }

    public function testWithInvalidFormat()
    {
        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('badformat');

        $request = $expectedRequest->duplicate();

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testWithInvalidPage()
    {
        $eventProphecy = $this->prophesize(EventInterface::class);

        $expectedRequest = new Request();
        $expectedRequest->setRequestFormat('jsonapi');

        $request = $expectedRequest->duplicate();
        $eventProphecy->getContext()->willReturn(['request' => $request]);
        $this->listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);

        $expectedRequest = $expectedRequest->duplicate(['page' => 'foo']);

        $request = $expectedRequest->duplicate();
        $eventProphecy->getContext()->willReturn(['request' => $request]);
        $this->listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($expectedRequest, $request);
    }

    public function testWithValidFilter()
    {
        $request = new Request(['page' => ['size' => 5, 'number' => 3, 'error' => -1]]);
        $request->setRequestFormat('jsonapi');

        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $this->listener->handleEvent($eventProphecy->reveal());

        $filters = ['size' => 5, 'number' => 3, 'error' => -1];

        $expectedRequest = new Request(['page' => $filters], [], ['_api_pagination' => $filters, '_api_filters' => $filters]);
        $expectedRequest->setRequestFormat('jsonapi');

        $this->assertEquals($expectedRequest, $request);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\JsonApi\EventListener\TransformPaginationParametersListener::onKernelRequest() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseEvent" as argument of "ApiPlatform\Core\JsonApi\EventListener\TransformPaginationParametersListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelRequest()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();

        $this->listener->onKernelRequest($eventProphecy->reveal());
    }
}
