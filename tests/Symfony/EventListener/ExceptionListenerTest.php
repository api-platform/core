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

namespace ApiPlatform\Tests\Symfony\EventListener;

use ApiPlatform\Symfony\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ExceptionListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getRequest
     */
    public function testOnKernelException(Request $request): void
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);

        $event = new ExceptionEvent($kernel->reveal(), $request, HttpKernelInterface::MAIN_REQUEST, new \Exception());

        $errorListener = $this->prophesize(ErrorListener::class);
        $errorListener->onKernelException($event)->shouldBeCalled();
        $listener = new ExceptionListener($errorListener->reveal());
        $listener->onKernelException($event);
    }

    public function getRequest(): array
    {
        return [
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation_name' => 'get'])],
            [new Request([], [], ['_api_respond' => true])],
        ];
    }

    public function testDoNothingWhenNotAnApiCall(): void
    {
        $errorListener = $this->prophesize(ErrorListener::class);
        $listener = new ExceptionListener($errorListener->reveal());
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), HttpKernelInterface::MAIN_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNothingWhenHtmlRequested(): void
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('html');

        $errorListener = $this->prophesize(ErrorListener::class);
        $listener = new ExceptionListener($errorListener->reveal());
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MAIN_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}
