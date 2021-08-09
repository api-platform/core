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

namespace ApiPlatform\Core\Tests\EventListener;

use ApiPlatform\Core\EventListener\ExceptionListener;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function testOnKernelException(Request $request)
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);
        $kernel->handle(Argument::type(Request::class), HttpKernelInterface::SUB_REQUEST, false)->willReturn(new Response())->shouldBeCalled();

        $listener = new ExceptionListener('foo:bar', null, false, class_exists(ErrorListener::class) ? $this->prophesize(ErrorListener::class)->reveal() : null);
        $event = new ExceptionEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertInstanceOf(Response::class, $event->getResponse());
    }

    public function getRequest()
    {
        return [
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_collection_operation_name' => 'get'])],
            [new Request([], [], ['_api_respond' => true])],
        ];
    }

    public function testDoNothingWhenNotAnApiCall()
    {
        $listener = new ExceptionListener('foo:bar', null, false, class_exists(ErrorListener::class) ? $this->prophesize(ErrorListener::class)->reveal() : null);
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), HttpKernelInterface::MASTER_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNothingWhenHtmlRequested()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('html');

        $listener = new ExceptionListener('foo:bar', null, false, class_exists(ErrorListener::class) ? $this->prophesize(ErrorListener::class)->reveal() : null);
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}
