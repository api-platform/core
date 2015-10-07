<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Controller;

use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Render a normalized exception for a given {@see \Symfony\Component\Debug\Exception\FlattenException}.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ExceptionController
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @param NormalizerInterface $normalizer
     */
    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * Converts a {@see \Symfony\Component\Debug\Exception\FlattenException}
     * to a {@see \Dunglas\ApiBundle\JsonLd\Response}.
     *
     * @param Request                   $request
     * @param FlattenException          $exception
     * @param DebugLoggerInterface|null $logger
     *
     * @return Response
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        if (
            'Dunglas\ApiBundle\Exception\DeserializationException' === $exception->getClass()
            || is_subclass_of($exception->getClass(), 'Dunglas\ApiBundle\Exception\DeserializationException')
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
