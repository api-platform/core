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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\EventListener\ValidationExceptionListener;
use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidationExceptionListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testNotValidationException()
    {
        $eventProphecy = $this->prophesize(ExceptionEvent::class);
        if (method_exists(ExceptionEvent::class, 'getThrowable')) {
            $eventProphecy->getThrowable()->willReturn(new \Exception())->shouldBeCalled();
        } else {
            $eventProphecy->getException()->willReturn(new \Exception())->shouldBeCalled();
        }
        $eventProphecy->setResponse()->shouldNotBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $listener->onKernelException($eventProphecy->reveal());
    }

    public function testValidationException()
    {
        $exceptionJson = '{"foo": "bar"}';
        $list = new ConstraintViolationList([]);

        $eventProphecy = $this->prophesize(ExceptionEvent::class);
        if (method_exists(ExceptionEvent::class, 'getThrowable')) {
            $eventProphecy->getThrowable()->willReturn(new ValidationException($list))->shouldBeCalled();
        } else {
            $eventProphecy->getException()->willReturn(new ValidationException($list))->shouldBeCalled();
        }
        $eventProphecy->getRequest()->willReturn(new Request())->shouldBeCalled();
        $eventProphecy->setResponse(Argument::allOf(
            Argument::type(Response::class),
            Argument::which('getContent', $exceptionJson),
            Argument::which('getStatusCode', Response::HTTP_BAD_REQUEST),
            Argument::that(function (Response $response): bool {
                return
                    'application/ld+json; charset=utf-8' === $response->headers->get('Content-Type')
                    && 'nosniff' === $response->headers->get('X-Content-Type-Options')
                    && 'deny' === $response->headers->get('X-Frame-Options');
            })
        ))->shouldBeCalled();

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($list, 'hydra')->willReturn($exceptionJson)->shouldBeCalled();

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $listener->onKernelException($eventProphecy->reveal());
    }
}
