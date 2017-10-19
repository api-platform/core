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
    public function testOnKernelRequest(Request $request, string $controller = null)
    {
        $eventProphecy = $this->prophesize(GetResponseEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new SwaggerUiListener();
        $listener->onKernelRequest($eventProphecy->reveal());

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
}
