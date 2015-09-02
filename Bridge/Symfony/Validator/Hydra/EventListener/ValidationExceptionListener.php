<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Symfony\Validator\Hydra\EventListener;

use Dunglas\ApiBundle\Bridge\Symfony\Validator\Exception\ValidationException;
use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Handles validation errors.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ValidationExceptionListener
{
    /**
     * @var NormalizerInterface
     */
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
