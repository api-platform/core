<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\JsonLd\Action;

use ApiPlatform\Builder\JsonLd\EntrypointBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generates the JSON-LD API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointAction
{
    private $entrypointBuilder;

    public function __construct(EntrypointBuilderInterface $entrypointBuilder)
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
    public function __invoke(Request $request) : array
    {
        $request->attributes->set('_api_format', 'jsonld');

        return $this->entrypointBuilder->getEntrypoint();
    }
}
