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
use ApiPlatform\Symfony\EventListener\ExceptionListener;
use ApiPlatform\Symfony\Validator\Exception\ConstraintViolationListAwareExceptionInterface as SymfonyConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Util\ErrorFormatGuesser;
use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles validation errors.
 * TODO: remove this class.
 *
 * @deprecated
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationExceptionListener
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly array $errorFormats, private readonly array $exceptionToStatus = [], private readonly ?ExceptionListener $exceptionListener = null)
    {
    }

    /**
     * Returns a list of violations normalized in the Hydra format.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // API Platform 3.2 handles every exception through the exception listener so we just skip this one
        if ($this->exceptionListener) {
            return;
        }

        trigger_deprecation('api-platform', '3.2', sprintf('The class "%s" is deprecated and will be removed in 4.x.', __CLASS__));

        $exception = $event->getThrowable();
        $hasConstraintViolationList = ($exception instanceof ConstraintViolationListAwareExceptionInterface || $exception instanceof SymfonyConstraintViolationListAwareExceptionInterface);
        if (!$hasConstraintViolationList && !$exception instanceof FilterValidationException) {
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
            $this->serializer->serialize($hasConstraintViolationList ? $exception->getConstraintViolationList() : $exception, $format['key'], $context),
            $statusCode,
            [
                'Content-Type' => sprintf('%s; charset=utf-8', $format['value'][0]),
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
            ]
        ));
    }
}
