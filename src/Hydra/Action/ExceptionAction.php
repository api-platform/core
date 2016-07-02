<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Hydra\Action;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Renders a normalized exception for a given {@see \Symfony\Component\Debug\Exception\FlattenException}.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class ExceptionAction
{
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Converts a an exception to a JSON response.
     */
    public function __invoke(FlattenException $exception) : JsonResponse
    {
        $exceptionClass = $exception->getClass();
        if (
            is_a($exceptionClass, ExceptionInterface::class, true) ||
            is_a($exceptionClass, InvalidArgumentException::class, true)
        ) {
            $exception->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
        }

        $headers = $exception->getHeaders();
        $headers['Content-Type'] = 'application/ld+json';

        return new JsonResponse($this->normalizer->normalize($exception, 'hydra-error'), $exception->getStatusCode(), $headers);
    }
}
