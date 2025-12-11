<?php

namespace ApiPlatform\Mcp\Routing;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Operation;

final class IriConverter implements IriConverterInterface
{
    public function __construct(private readonly IriConverterInterface $inner)
    {
    }

    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object
    {
        return $this->inner->getResourceFromIri($iri, $context, $operation);
    }

    public function getIriFromResource(object|string $resource, int $referenceType = 1, ?Operation $operation = null, array $context = []): ?string
    {
        if (($operation instanceof McpTool || $operation instanceof McpResource) && !isset($context['item_uri_template'])) {
            return null;
        }

        return $this->inner->getIriFromResource($resource, $referenceType, $operation, $context);
    }
}
