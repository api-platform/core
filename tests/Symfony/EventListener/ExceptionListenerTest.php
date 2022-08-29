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

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Symfony\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
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
    public function testOnKernelException(Request $request)
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);

        $errorListener = class_exists(ErrorListener::class) ? $this->prophesize(ErrorListener::class) : null;

        $event = new ExceptionEvent($kernel->reveal(), $request, \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST, new \Exception());

        if ($errorListener) {
            $errorListener->onKernelException($event)->shouldBeCalled();
        }

        $listener = new ExceptionListener('foo:bar', null, false, $errorListener ? $errorListener->reveal() : null);
        $listener->onKernelException($event);
    }

    public function getRequest()
    {
        return [
            [new Request([], [], ['_api_resource_class' => 'Foo', '_api_operation' => new Get(), '_api_respond' => true])],
        ];
    }

    public function testDoNothingWhenNotAnApiCall()
    {
        $listener = new ExceptionListener('foo:bar', null, false, class_exists(ErrorListener::class) ? $this->prophesize(ErrorListener::class)->reveal() : null);
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    public function testDoNothingWhenHtmlRequested()
    {
        $request = new Request([], [], ['_api_respond' => true]);
        $request->setRequestFormat('html');

        $listener = new ExceptionListener('foo:bar', null, false, class_exists(ErrorListener::class) ? $this->prophesize(ErrorListener::class)->reveal() : null);
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, \defined(HttpKernelInterface::class.'::MAIN_REQUEST') ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::MASTER_REQUEST, new \Exception());
        $listener->onKernelException($event);

        $this->assertNull($event->getResponse());
    }
}
