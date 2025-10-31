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

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Creates MCP capability definitions from API Platform operations.
 *
 * @internal
 */
final readonly class McpOperationFactory implements McpCapabilityFactoryInterface
{
    public function __construct(
        private ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private RequestContext $requestContext,
        private SchemaFactoryInterface $schemaFactory,
    ) {
    }

    /**
     * Creates and yields MCP capability definitions.
     *
     * @return \Generator<array{type: string, definition: array}>
     */
    public function create(): \Generator
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $resourceMetadataCollection = $this->resourceMetadataFactory->create($resourceClass);
            foreach ($resourceMetadataCollection as $resource) {
                foreach ($resource->getOperations() as $operation) {
                    // TODO: A dedicated `$operation->getMcp() === false` property would be the ideal way to control inclusion.
                    if (!$operation instanceof HttpOperation) {
                        continue;
                    }

                    $mcpName = $operation->getExtraProperties()['mcp_name'] ?? null;
                    if (!$mcpName) {
                        continue;
                    }

                    // TBD: To support multiple formats, we could iterate over `getOutputFormats`
                    // and yield a tool for each, suffixing the name with the format,
                    // e.g., "api_books_get_collection_jsonld", "api_books_get_collection_json".
                    // The handler would then parse this name to set the correct `Accept` header.
                    $method = strtoupper($operation->getMethod());

                    if (!$operation->getUriTemplate()) {
                        continue;
                    }

                    $mimeType = current($operation->getInputFormats())[0] ?? 'application/json';

                    if ('GET' === $method) {
                        $uri = \sprintf('%s://%s/%s', $this->requestContext->getScheme(), $this->requestContext->getHost(), ltrim(str_replace('{._format}', '', $operation->getUriTemplate()), '/'));

                        if (!$operation->getUriVariables()) {
                            yield [
                                'type' => 'resource',
                                'definition' => [
                                    'uri' => $uri,
                                    'name' => $mcpName,
                                    'description' => $operation->getDescription(),
                                    'mimeType' => $mimeType,
                                ],
                            ];

                            continue;
                        }

                        yield [
                            'type' => 'resource_template',
                            'definition' => [
                                'uriTemplate' => $uri,
                                'name' => $mcpName,
                                'description' => $operation->getDescription(),
                                'mimeType' => $mimeType,
                            ],
                        ];
                        continue;
                    }

                    yield [
                        'type' => 'tool',
                        'definition' => [
                            'name' => $mcpName,
                            'description' => $operation->getDescription(),
                            'inputSchema' => $this->buildInputSchema($operation),
                        ],
                    ];
                }
            }
        }
    }

    private function buildInputSchema(HttpOperation $operation): ?array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        // 1. Add properties from the request body for relevant methods
        if (\in_array($operation->getMethod(), ['POST', 'PUT', 'PATCH'], true)) {
            $bodySchema = $this->schemaFactory->buildSchema($operation->getClass(), 'json', Schema::TYPE_INPUT, $operation);
            $rootDefinitionKey = $bodySchema->getRootDefinitionKey();

            if (null !== $rootDefinitionKey && isset($bodySchema->getDefinitions()[$rootDefinitionKey])) {
                $bodyDefinition = $bodySchema->getDefinitions()[$rootDefinitionKey]->getArrayCopy();
                if (isset($bodyDefinition['properties'])) {
                    $schema['properties'] = array_merge($schema['properties'], $bodyDefinition['properties']);
                }
                if (isset($bodyDefinition['required'])) {
                    $schema['required'] = array_merge($schema['required'], $bodyDefinition['required']);
                }
            }
        }

        // 2. Add properties from URI variables
        foreach ($operation->getUriVariables() as $parameterName => $uriVariable) {
            $schema['properties'][$parameterName] = $uriVariable->getSchema() ?? ['type' => 'string'];
            if ($uriVariable->getRequired() ?? true) {
                $schema['required'][] = $parameterName;
            }
        }

        if (empty($schema['properties'])) {
            return null;
        }

        if (empty($schema['required'])) {
            unset($schema['required']);
        } else {
            // Ensure unique values
            $schema['required'] = array_values(array_unique($schema['required']));
        }

        return $schema;
    }
}
