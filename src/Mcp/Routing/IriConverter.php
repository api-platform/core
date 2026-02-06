<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Mcp\Routing;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;

/**
 * @experimental
 */
final class IriConverter implements IriConverterInterface
{
    public function __construct(private readonly IriConverterInterface $inner)
    {
    }

    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object
    {
        return $this->inner->getResourceFromIri($iri, $context, $operation);
    }

    public function getIriFromResource(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = []): ?string
    {
        if (($operation instanceof McpTool || $operation instanceof McpResource) && !isset($context['item_uri_template'])) {
            return null;
        }

        return $this->inner->getIriFromResource($resource, $referenceType, $operation, $context);
    }
}
