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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Generates a Hydra API documentation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class HydraController extends Controller
{
    /**
     * Namespace of types specific to the current API.
     *
     * @return Response
     */
    public function vocabAction()
    {
        return new Response($this->get('api.hydra.documentation_builder')->getApiDocumentation());
    }
}
