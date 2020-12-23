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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
        $listener = new ValidationExceptionListener(
            $this->prophesize(SerializerInterface::class)->reveal(),
            ['hydra' => ['application/ld+json']]);

        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), HttpKernelInterface::MASTER_REQUEST, new \Exception());
        $listener->onKernelException($event);
        $this->assertNull($event->getResponse());
    }

    public function testValidationException()
    {
        $exceptionJson = '{"foo": "bar"}';
        $list = new ConstraintViolationList([]);

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($list, 'hydra')->willReturn($exceptionJson)->shouldBeCalled();

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), HttpKernelInterface::MASTER_REQUEST, new ValidationException($list));
        $listener->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($exceptionJson, $response->getContent());
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertSame('application/ld+json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('deny', $response->headers->get('X-Frame-Options'));
    }
}
