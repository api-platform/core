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
use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Exception\FilterValidationException;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Core\Validator\Exception\ValidationException as BaseValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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

    public function testOnKernelValidationExceptionWithCustomStatus(): void
    {
        $serializedConstraintViolationList = '{"foo": "bar"}';
        $constraintViolationList = new ConstraintViolationList([]);
        $exception = new class($constraintViolationList) extends BaseValidationException implements ConstraintViolationListAwareExceptionInterface {
            private $constraintViolationList;

            public function __construct(ConstraintViolationListInterface $constraintViolationList, $message = '', $code = 0, \Throwable $previous = null)
            {
                parent::__construct($message, $code, $previous);

                $this->constraintViolationList = $constraintViolationList;
            }

            public function getConstraintViolationList(): ConstraintViolationListInterface
            {
                return $this->constraintViolationList;
            }
        };

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($constraintViolationList, 'hydra')->willReturn($serializedConstraintViolationList)->shouldBeCalledOnce();

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        (new ValidationExceptionListener(
            $serializerProphecy->reveal(),
            ['hydra' => ['application/ld+json']],
            [\get_class($exception) => Response::HTTP_BAD_REQUEST]
        ))->onKernelException($exceptionEvent);

        $response = $exceptionEvent->getResponse();

        self::assertInstanceOf(Response::class, $response);
        self::assertSame($serializedConstraintViolationList, $response->getContent());
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('application/ld+json; charset=utf-8', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('deny', $response->headers->get('X-Frame-Options'));
    }

    public function testValidationFilterException()
    {
        $exceptionJson = '{"message": "my message"}';
        $exception = new FilterValidationException([], 'my message');

        $serializerProphecy = $this->prophesize(SerializerInterface::class);
        $serializerProphecy->serialize($exception, 'hydra')->willReturn($exceptionJson)->shouldBeCalled();

        $listener = new ValidationExceptionListener($serializerProphecy->reveal(), ['hydra' => ['application/ld+json']]);
        $event = new ExceptionEvent($this->prophesize(HttpKernelInterface::class)->reveal(), new Request(), HttpKernelInterface::MASTER_REQUEST, $exception);
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
