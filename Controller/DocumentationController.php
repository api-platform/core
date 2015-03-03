<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Controller;

use Dunglas\JsonLdApiBundle\Response\JsonLdResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Generates API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DocumentationController extends Controller
{
    private static $reservedShortNames = [
        'Entrypoint' => true,
        'ConstraintViolationList' => true,
        'ApiDocumentation' => true,
        'Error' => true,
    ];

    /**
     * Serves the entrypoint of the API.
     *
     * @return JsonLdResponse
     */
    public function entrypointAction()
    {
        return new JsonLdResponse($this->get('dunglas_json_ld_api.entrypoint_builder')->getEntrypoint());
    }

    /**
     * Namespace of types specific to the current API.
     *
     * @return JsonLdResponse
     */
    public function vocabAction()
    {
        return new JsonLdResponse($this->get('dunglas_json_ld_api.api_documentation_builder')->getApiDocumentation());
    }

    /**
     * JSON-LD context for a given type.
     *
     * @param string $shortName
     *
     * @return JsonLdResponse
     */
    public function contextAction($shortName)
    {
        if (isset(self::$reservedShortNames[$shortName])) {
            $resource = null;
        } else {
            $resource = $this->get('dunglas_json_ld_api.resources')->getResourceForShortName($shortName);
            if (!$resource) {
                throw $this->createNotFoundException();
            }
        }

        return new JsonLdResponse(
            ['@context' => $this->get('dunglas_json_ld_api.context_builder')->buildContext($resource)]
        );
    }
}
