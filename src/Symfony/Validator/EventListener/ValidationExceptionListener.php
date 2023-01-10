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

namespace ApiPlatform\Symfony\Validator\EventListener;

use ApiPlatform\Exception\FilterValidationException;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles validation errors.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationExceptionListener
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly array $errorFormats, private readonly array $exceptionToStatus = [])
    {
    }

    /**
     * Returns a list of violations normalized in the Hydra format.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof ConstraintViolationListAwareExceptionInterface && !$exception instanceof FilterValidationException) {
            return;
        }
        $exceptionClass = $exception::class;
        $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;

        foreach ($this->exceptionToStatus as $class => $status) {
            if (is_a($exceptionClass, $class, true)) {
                $statusCode = $status;

                break;
            }
        }

        $format = ErrorFormatGuesser::guessErrorFormat($event->getRequest(), $this->errorFormats);

        $context = [];
        if ($exception instanceof ValidationException && ($errorTitle = $exception->getErrorTitle())) {
            $context['title'] = $errorTitle;
        }

        $event->setResponse(new Response(
            $this->serializer->serialize($exception instanceof ConstraintViolationListAwareExceptionInterface ? $exception->getConstraintViolationList() : $exception, $format['key'], $context),
            $statusCode,
            [
                'Content-Type' => sprintf('%s; charset=utf-8', $format['value'][0]),
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
            ]
        ));
    }
}
