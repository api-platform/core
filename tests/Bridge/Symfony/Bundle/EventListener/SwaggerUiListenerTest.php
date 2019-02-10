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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener\SwaggerUiListener;
use ApiPlatform\Core\Event\EventInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SwaggerUiListenerTest extends TestCase
{
    /**
     * @dataProvider getParameters
     */
    public function testSetController(Request $request, string $controller = null)
    {
        $eventProphecy = $this->prophesize(EventInterface::class);
        $eventProphecy->getContext()->willReturn(['request' => $request]);

        $listener = new SwaggerUiListener();
        $listener->handleEvent($eventProphecy->reveal());

        $this->assertEquals($controller, $request->attributes->get('_controller'));
    }

    public function getParameters()
    {
        $respondRequest = new Request([], [], ['_api_respond' => true]);
        $respondRequest->setRequestFormat('html');

        $resourceClassRequest = new Request([], [], ['_api_resource_class' => 'Foo']);
        $resourceClassRequest->setRequestFormat('html');

        $jsonRequest = new Request([], [], ['_api_resource_class' => 'Foo']);
        $jsonRequest->setRequestFormat('json');

        return [
            [$respondRequest, 'api_platform.swagger.action.ui'],
            [$resourceClassRequest, 'api_platform.swagger.action.ui'],
            [new Request(), null],
            [$jsonRequest, null],
        ];
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The method ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener\SwaggerUiListener::onKernelRequest() is deprecated since 2.5 and will be removed in 3.0.
     * @expectedDeprecation Passing an instance of "Symfony\Component\HttpKernel\Event\GetResponseEvent" as argument of "ApiPlatform\Core\Bridge\Symfony\Bundle\EventListener\SwaggerUiListener::handleEvent" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "ApiPlatform\Core\Event\EventInterface" instead.
     */
    public function testLegacyOnKernelRequest()
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request());

        $listener = new SwaggerUiListener();
        $listener->onKernelRequest($eventProphecy->reveal());
    }
}
