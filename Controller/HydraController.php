<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Controller;

use Dunglas\ApiBundle\Exception\DeserializationException;
use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;

/**
 * Generates a Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class HydraController extends Controller
{
    /**
     * Namespace of types specific to the current API.
     *
     * @return Response
     */
    public function docAction()
    {
        return new Response($this->get('api.hydra.documentation_builder')->getApiDocumentation());
    }

    /**
     * Converts a {@see \Symfony\Component\Debug\Exception\FlattenException}
     * to a {@see \Dunglas\ApiBundle\JsonLd\Response}.
     *
     * @param FlattenException $exception
     *
     * @return Response
     */
    public function exceptionAction(FlattenException $exception)
    {
        if (is_a($exception->getClass(), DeserializationException::class, true)) {
            $exception->setStatusCode(Response::HTTP_BAD_REQUEST);
        }

        return new Response(
            $this->get('serializer')->normalize($exception, 'hydra-error'),
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
    }
}
