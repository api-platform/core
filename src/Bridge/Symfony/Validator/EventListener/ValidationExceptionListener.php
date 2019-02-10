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

namespace ApiPlatform\Core\Bridge\Symfony\Validator\EventListener;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Event\EventInterface;
use ApiPlatform\Core\Util\ErrorFormatGuesser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Handles validation errors.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationExceptionListener
{
    private $serializer;
    private $errorFormats;

    public function __construct(SerializerInterface $serializer, array $errorFormats)
    {
        $this->serializer = $serializer;
        $this->errorFormats = $errorFormats;
    }

    /**
     * Returns a list of violations normalized in the Hydra format.
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        @trigger_error(sprintf('The method %s() is deprecated since 2.5 and will be removed in 3.0.', __METHOD__), E_USER_DEPRECATED);

        $this->handleEvent($event);
    }

    /**
     * Returns a list of violations normalized in the Hydra format.
     */
    public function handleEvent(/*EventInterface */$event): void
    {
        if ($event instanceof EventInterface) {
            $exception = $event->getContext()['exception'];
        } elseif ($event instanceof GetResponseForExceptionEvent) {
            @trigger_error(sprintf('Passing an instance of "%s" as argument of "%s" is deprecated since 2.5 and will not be possible anymore in 3.0. Pass an instance of "%s" instead.', GetResponseForExceptionEvent::class, __METHOD__, EventInterface::class), E_USER_DEPRECATED);

            $exception = $event->getException();
        } else {
            return;
        }

        if (!$exception instanceof ValidationException) {
            return;
        }

        if ($event instanceof GetResponseForExceptionEvent) {
            $request = $event->getRequest();
        } elseif ($event instanceof EventInterface) {
            $request = $event->getContext()['request'];
        } else {
            return;
        }

        $format = ErrorFormatGuesser::guessErrorFormat($request, $this->errorFormats);

        $response = new Response(
            $this->serializer->serialize($exception->getConstraintViolationList(), $format['key']),
            Response::HTTP_BAD_REQUEST,
            [
                'Content-Type' => sprintf('%s; charset=utf-8', $format['value'][0]),
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'deny',
            ]
        );

        if ($event instanceof EventInterface) {
            $event->setData($response);
        } elseif ($event instanceof GetResponseForExceptionEvent) {
            $event->setResponse($response);
        }
    }
}
