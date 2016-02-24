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
use ApiPlatform\Core\JsonLd\Response;
use Symfony\Component\Debug\Exception\FlattenException;
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
     * Converts a {@see \Symfony\Component\Debug\Exception\FlattenException}
     * to a {@see \ApiPlatform\Core\JsonLd\Response}.
     *
     * @param FlattenException $exception
     *
     * @return Response
     */
    public function __invoke(FlattenException $exception)
    {
        $exceptionClass = $exception->getClass();
        if (
            is_a($exceptionClass, ExceptionInterface::class, true) ||
            is_a($exceptionClass, InvalidArgumentException::class, true)
        ) {
            $exception->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        return new Response(
            $this->normalizer->normalize($exception, 'hydra-error'),
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }
}
