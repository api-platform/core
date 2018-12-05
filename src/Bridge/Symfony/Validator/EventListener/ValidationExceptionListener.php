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
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
        if (!$exception instanceof ValidationException) {
            return;
        }

        $format = ErrorFormatGuesser::guessErrorFormat($event->getRequest(), $this->errorFormats);

        $event->setResponse(new Response(
                $this->serializer->serialize($exception->getConstraintViolationList(), $format['key']),
                Response::HTTP_BAD_REQUEST,
                [
                    'Content-Type' => sprintf('%s; charset=utf-8', $format['value'][0]),
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'deny',
                ]
        ));
    }
}
