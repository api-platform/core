<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Symfony\Validator\Hydra\EventListener;

use ApiPlatform\Builder\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Builder\JsonLd\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Handles validation errors.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationExceptionListener
{
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Returns a list of violations normalized in the Hydra format.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ValidationException) {
            $event->setResponse(new Response(
                $this->normalizer->normalize($exception->getConstraintViolationList(), 'hydra-error'),
                Response::HTTP_BAD_REQUEST
            ));
        }
    }
}
