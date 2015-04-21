<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Hydra\EventListener;

use Dunglas\ApiBundle\Exception\DeserializationException;
use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Handle requests errors.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestExceptionListener
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
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $exception = $event->getException();

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        } elseif ($exception instanceof DeserializationException) {
            $status = Response::HTTP_BAD_REQUEST;
            $headers = [];
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $headers = [];
        }

        // Normalize exceptions with hydra errors only for resources
        if ($request->attributes->has('_resource')) {
            $event->setResponse(new Response(
                $this->normalizer->normalize($exception, 'hydra-error'),
                $status,
                $headers
            ));
        }
    }
}
