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

namespace ApiPlatform\Mcp\Tests\Capability\Registry;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Mcp\Capability\Registry\Loader;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\Tool;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{
    public function testToolRegistrationWithFlatSchema(): void
    {
        $inputSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($inputSchema['$schema']);
        $inputSchema['type'] = 'object';
        $inputSchema['properties'] = ['name' => ['type' => 'string']];

        $outputSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($outputSchema['$schema']);
        $outputSchema['type'] = 'object';
        $outputSchema['properties'] = ['id' => ['type' => 'integer'], 'name' => ['type' => 'string']];

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturnOnConsecutiveCalls($inputSchema, $outputSchema);

        $mcpTool = new McpTool(
            name: 'createDummy',
            description: 'Creates a dummy',
            class: \stdClass::class,
        );

        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['createDummy' => $mcpTool]);

        $nameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([\stdClass::class]));

        $metadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [$resource]));

        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->once())
            ->method('registerTool')
            ->with(
                $this->callback(function (Tool $tool): bool {
                    $this->assertSame('createDummy', $tool->name);
                    $this->assertSame('Creates a dummy', $tool->description);
                    $this->assertSame(['type' => 'object', 'properties' => ['name' => ['type' => 'string']]], $tool->inputSchema);
                    $this->assertSame(['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'name' => ['type' => 'string']]], $tool->outputSchema);

                    return true;
                }),
                Loader::HANDLER,
                true,
            );

        $loader = new Loader($nameCollectionFactory, $metadataCollectionFactory, $schemaFactory);
        $loader->load($registry);
    }

    public function testStructuredContentFalseSkipsOutputSchema(): void
    {
        $inputSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($inputSchema['$schema']);
        $inputSchema['type'] = 'object';
        $inputSchema['properties'] = ['query' => ['type' => 'string']];

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($inputSchema);

        $mcpTool = new McpTool(
            name: 'search',
            description: 'Search things',
            structuredContent: false,
            class: \stdClass::class,
        );

        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['search' => $mcpTool]);

        $nameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([\stdClass::class]));

        $metadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [$resource]));

        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->once())
            ->method('registerTool')
            ->with(
                $this->callback(function (Tool $tool): bool {
                    $this->assertSame('search', $tool->name);
                    $this->assertNull($tool->outputSchema);

                    return true;
                }),
                Loader::HANDLER,
                true,
            );

        $loader = new Loader($nameCollectionFactory, $metadataCollectionFactory, $schemaFactory);
        $loader->load($registry);
    }

    public function testResourceRegistration(): void
    {
        $mcpResource = new McpResource(
            uri: 'dummy://docs',
            name: 'docs',
            description: 'Documentation resource',
            mimeType: 'text/plain',
            class: \stdClass::class,
        );

        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['docs' => $mcpResource]);

        $nameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([\stdClass::class]));

        $metadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [$resource]));

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);

        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->once())
            ->method('registerResource')
            ->with(
                $this->callback(function ($resource): bool {
                    $this->assertSame('dummy://docs', $resource->uri);
                    $this->assertSame('docs', $resource->name);
                    $this->assertSame('Documentation resource', $resource->description);
                    $this->assertSame('text/plain', $resource->mimeType);

                    return true;
                }),
                Loader::HANDLER,
                true,
            );

        $loader = new Loader($nameCollectionFactory, $metadataCollectionFactory, $schemaFactory);
        $loader->load($registry);
    }

    public function testEmptyMcpIsSkipped(): void
    {
        $resource = new ApiResource(class: \stdClass::class);

        $nameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([\stdClass::class]));

        $metadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [$resource]));

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);

        $registry = $this->createMock(RegistryInterface::class);
        $registry->expects($this->never())->method('registerTool');
        $registry->expects($this->never())->method('registerResource');

        $loader = new Loader($nameCollectionFactory, $metadataCollectionFactory, $schemaFactory);
        $loader->load($registry);
    }
}
