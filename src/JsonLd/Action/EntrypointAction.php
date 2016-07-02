<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonLd\Action;

use ApiPlatform\Core\JsonLd\EntrypointBuilderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public function __invoke(Request $request) : JsonResponse
    {
        return new JsonResponse($this->entrypointBuilder->getEntrypoint(), 200, ['Content-Type' => 'application/ld+json']);
    }
}
