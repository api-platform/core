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

use Dunglas\ApiBundle\JsonLd\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * JSON-LD contexts and entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class JsonLdController extends Controller
{
    /**
     * @var array
     */
    private static $reservedShortNames = [
        'ConstraintViolationList' => true,
        'Error' => true,
    ];

    /**
     * Serves the entrypoint of the API.
     *
     * @return Response
     */
    public function entrypointAction()
    {
        return new Response($this->get('api.json_ld.entrypoint_builder')->getEntrypoint());
    }

    /**
     * JSON-LD context for the entrypoint.
     *
     * @return Response
     */
    public function entrypointContextAction()
    {
        return new Response(
            ['@context' => $this->get('api.json_ld.context_builder')->getEntrypointContext()]
        );
    }

    /**
     * JSON-LD context for a given type.
     *
     * @param string $shortName
     *
     * @return Response
     */
    public function contextAction($shortName)
    {
        if (isset(self::$reservedShortNames[$shortName])) {
            $resource = null;
        } else {
            $resource = $this->get('api.resource_collection')->getResourceForShortName($shortName);
            if (!$resource) {
                throw $this->createNotFoundException();
            }
        }

        return new Response(
            ['@context' => $this->get('api.json_ld.context_builder')->getContext($resource)]
        );
    }
}
