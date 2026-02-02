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

namespace ApiPlatform\Mcp\Capability\Registry;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\Annotations;
use Mcp\Schema\Resource;
use Mcp\Schema\Tool;
use Mcp\Schema\ToolAnnotations;

final class Loader implements LoaderInterface
{
    public const HANDLER = 'api_platform.mcp.handler';

    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollection,
        private readonly SchemaFactoryInterface $schemaFactory,
    ) {
    }

    public function load(RegistryInterface $registry): void
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $metadata = $this->resourceMetadataCollection->create($resourceClass);

            foreach ($metadata as $resource) {
                foreach ($resource->getMcp() ?? [] as $mcp) {
                    if ($mcp instanceof McpTool) {
                        $inputClass = $mcp->getInput()['class'] ?? $mcp->getClass();
                        $inputFormat = array_first($mcp->getInputFormats() ?? ['json']);
                        $inputSchema = $this->schemaFactory->buildSchema($inputClass, $inputFormat, Schema::TYPE_INPUT, $mcp, null, [SchemaFactory::FORCE_SUBSCHEMA => true]);

                        $outputClass = $mcp->getOutput()['class'] ?? $mcp->getClass();
                        $outputFormat = array_first($mcp->getOutputFormats() ?? ['jsonld']);
                        $outputSchema = $this->schemaFactory->buildSchema($outputClass, $outputFormat, Schema::TYPE_OUTPUT, $mcp, null, [SchemaFactory::FORCE_SUBSCHEMA => true]);

                        $registry->registerTool(
                            new Tool(
                                name: $mcp->getName(),
                                inputSchema: $inputSchema->getDefinitions()[$inputSchema->getRootDefinitionKey()]->getArrayCopy(),
                                description: $mcp->getDescription(),
                                annotations: $mcp->getAnnotations() ? ToolAnnotations::fromArray($mcp->getAnnotations()) : null,
                                icons: $mcp->getIcons(),
                                meta: $mcp->getMeta(),
                                outputSchema: $outputSchema->getDefinitions()[$outputSchema->getRootDefinitionKey()]->getArrayCopy(),
                                // outputSchema: $outputSchema->getArrayCopy(),
                            ),
                            self::HANDLER,
                            true,
                        );
                    }

                    if ($mcp instanceof McpResource) {
                        $registry->registerResource(
                            new Resource(
                                uri: $mcp->getUri(),
                                name: $mcp->getName(),
                                description: $mcp->getDescription(),
                                mimeType: $mcp->getMimeType(),
                                annotations: $mcp->getAnnotations() ? Annotations::fromArray($mcp->getAnnotations()) : null,
                                size: $mcp->getSize(),
                                icons: $mcp->getIcons(),
                                meta: $mcp->getMeta()
                            ),
                            self::HANDLER,
                            true,
                        );
                    }
                }
            }
        }
    }
}
