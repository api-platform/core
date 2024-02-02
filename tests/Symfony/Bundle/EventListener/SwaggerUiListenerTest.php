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

namespace ApiPlatform\Tests\Symfony\Bundle\EventListener;

use ApiPlatform\Symfony\Bundle\EventListener\SwaggerUiListener;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SwaggerUiListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getParameters
     */
    public function testOnKernelRequest(Request $request, ?string $controller = null): void
    {
        $eventProphecy = $this->prophesize(RequestEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new SwaggerUiListener();
        $listener->onKernelRequest($eventProphecy->reveal());

        $this->assertSame($controller, $request->attributes->get('_controller'));
    }

    public static function getParameters(): array
    {
        $respondRequest = new Request([], [], ['_api_respond' => true]);
        $respondRequest->setRequestFormat('html');

        $resourceClassRequest = new Request([], [], ['_api_resource_class' => 'Foo']);
        $resourceClassRequest->setRequestFormat('html');

        $jsonRequest = new Request([], [], ['_api_resource_class' => 'Foo']);
        $jsonRequest->setRequestFormat('json');

        return [
            [$respondRequest, 'api_platform.swagger_ui.action'],
            [$resourceClassRequest, 'api_platform.swagger_ui.action'],
            [new Request(), null],
            [$jsonRequest, null],
        ];
    }
}
