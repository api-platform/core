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

namespace ApiPlatform\Mcp\Factory;

use Symfony\Component\Routing\RequestContext;

/**
 * Creates MCP Resource definitions for API Platform's documentation endpoints.
 *
 * @internal
 */
final readonly class McpDocumentationFactory implements McpCapabilityFactoryInterface
{
    public function __construct(
        private RequestContext $requestContext,
    ) {
    }

    /**
     * @return \Generator<array{type: string, definition: array}>
     */
    public function create(): \Generator
    {
        $prefix = \sprintf('%s://%s/', $this->requestContext->getScheme(), $this->requestContext->getHost());

        // API Documentation Resources
        yield [
            'type' => 'resource',
            'definition' => [
                'uri' => $prefix.'docs.jsonopenapi',
                'name' => 'openapi_spec',
                'description' => 'The OpenAPI specification for this API.',
                'mimeType' => 'application/vnd.openapi+json',
            ],
        ];
        yield [
            'type' => 'resource',
            'definition' => [
                'uri' => $prefix.'docs.jsonld',
                'name' => 'hydra_docs',
                'description' => 'The Hydra documentation for this API.',
                'mimeType' => 'application/ld+json',
            ],
        ];

        // Entrypoint Resource
        yield [
            'type' => 'resource',
            'definition' => [
                'uri' => $prefix.'entrypoint',
                'name' => 'api_entrypoint',
                'description' => 'The main entrypoint for the API.',
                'mimeType' => 'application/ld+json',
            ],
        ];

        // JSON-LD Context Resource Template
        yield [
            'type' => 'resource_template',
            'definition' => [
                'uriTemplate' => $prefix.'contexts/{shortName}',
                'name' => 'jsonld_context',
                'description' => 'The JSON-LD context for a given resource short name.',
                'mimeType' => 'application/ld+json',
            ],
        ];
    }
}
