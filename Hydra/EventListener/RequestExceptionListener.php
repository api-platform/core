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

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Exception\ValidationException;
use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Handle requests errors.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class RequestExceptionListener
{
    const FORMAT = 'jsonld';

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
        $request = $event->getRequest();
        if (!$request->attributes->has('_resource_type') || self::FORMAT !== $request->attributes->get('_api_format')) {
            return;
        }

        $exception = $event->getException();
        $headers = [];

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
            $headers = $exception->getHeaders();
            $data = $exception;
        } elseif ($exception instanceof ValidationException) {
            $status = Response::HTTP_BAD_REQUEST;
            $data = $exception->getConstraintViolationList();
        } elseif ($exception instanceof ExceptionInterface || $exception instanceof InvalidArgumentException) {
            $status = Response::HTTP_BAD_REQUEST;
            $data = $exception;
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $data = $exception;
        }

        $event->setResponse(new Response(
            $this->normalizer->normalize($data, 'hydra-error'),
            $status,
            $headers
        ));
    }
}
