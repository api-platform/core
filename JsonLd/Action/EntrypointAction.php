<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Action;

use Dunglas\ApiBundle\JsonLd\EntrypointBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the JSON-LD API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class EntrypointAction
{
    /**
     * @var EntrypointBuilder
     */
    private $entrypointBuilder;

    public function __construct(EntrypointBuilder $entrypointBuilder)
    {
        $this->entrypointBuilder = $entrypointBuilder;
    }

    /**
     * Builds the entrypoint.
     *
     * @param Request $request
     *
     * @return array
     */
    public function __invoke(Request $request)
    {
        $request->attributes->set('_api_format', 'jsonld');

        return $this->entrypointBuilder->getEntrypoint();
    }
}
