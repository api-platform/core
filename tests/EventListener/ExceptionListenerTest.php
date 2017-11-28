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
use ApiPlatform\Core\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ExceptionListenerTest extends TestCase
{
    /**
     * @dataProvider getRequest
     */
    public function testOnKernelException(Request $request)
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);
        $kernel->handle(Argument::type(Request::class), HttpKernelInterface::SUB_REQUEST, false)->willReturn(new Response())->shouldBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();
        $eventProphecy->getException()->willReturn(new \Exception())->shouldBeCalled();
        $eventProphecy->getKernel()->willReturn($kernel)->shouldBeCalled();
        $eventProphecy->setResponse(Argument::type(Response::class))->shouldBeCalled();

        $listener = new ExceptionListener('foo:bar');
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function getRequest()
    {
        return [
            [new Request([], [], ['_api_resource_class' => 'Foo'])],
            [new Request([], [], ['_api_respond' => true])],
        ];
    }

    public function testDoNothingWhenNotAnApiCall()
    {
        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();

        $listener = new ExceptionListener('foo:bar');
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function testDoNothingWhenHtmlRequested()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('html');

        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getRequest()->willReturn($request)->shouldBeCalled();

        $listener = new ExceptionListener('foo:bar');
        $listener->onKernelException($eventProphecy->reveal());
    }

    /**
     * @dataProvider dataLogLevel
     */
    public function testLogLevel($exceptionClass, $critical)
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);
        $kernel->handle(Argument::type(Request::class), HttpKernelInterface::SUB_REQUEST, false)->willReturn(new Response())->shouldBeCalled();

        $eventProphecy = $this->prophesize(GetResponseForExceptionEvent::class);
        $eventProphecy->getRequest()->willReturn(new Request([], [], ['_api_respond' => true]))->shouldBeCalled();
        $eventProphecy->getException()->willReturn(new $exceptionClass())->shouldBeCalled();
        $eventProphecy->getKernel()->willReturn($kernel)->shouldBeCalled();
        $eventProphecy->setResponse(Argument::type(Response::class))->shouldBeCalled();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->critical(Argument::cetera())->{$critical ? 'shouldBeCalled' : 'shouldNotBeCalled'}();
        $loggerProphecy->error(Argument::cetera())->{$critical ? 'shouldNotBeCalled' : 'shouldBeCalled'}();

        $listener = new ExceptionListener('foo:bar', $loggerProphecy->reveal(), [InvalidArgumentException::class => 400]);
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function dataLogLevel()
    {
        return [
            [\Exception::class, true],
            [NotFoundHttpException::class, false],
            [InvalidArgumentException::class, false],
        ];
    }
}
