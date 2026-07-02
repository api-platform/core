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

namespace ApiPlatform\Mcp\Tests\Server;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Mcp\Capability\Registry\Loader;
use ApiPlatform\Mcp\Server\ListHandler;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use Mcp\Capability\Registry;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Mcp\Schema\Request\ListResourcesRequest;
use Mcp\Schema\Request\ListToolsRequest;
use Mcp\Schema\Result\ListResourcesResult;
use Mcp\Schema\Result\ListToolsResult;
use Mcp\Schema\Tool;
use Mcp\Server\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

class ListHandlerTest extends TestCase
{
    public function testListToolsLoadsApiPlatformElementsIntoTheRegistry(): void
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

        $registry = new Registry();
        $handler = new ListHandler($registry, $this->createLoader($resource, $schemaFactory));

        $result = $handler->handle((new ListToolsRequest())->withId(1), $this->createMock(SessionInterface::class))->result;

        $this->assertInstanceOf(ListToolsResult::class, $result);
        $this->assertCount(1, $result->tools);
        $this->assertSame('search', $result->tools[0]->name);
    }

    public function testListResourcesLoadsApiPlatformElementsIntoTheRegistry(): void
    {
        $mcpResource = new McpResource(
            uri: 'dummy://docs',
            name: 'docs',
            description: 'Documentation resource',
            mimeType: 'text/plain',
            class: \stdClass::class,
        );

        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['docs' => $mcpResource]);

        $registry = new Registry();
        $handler = new ListHandler($registry, $this->createLoader($resource, $this->createMock(SchemaFactoryInterface::class)));

        $result = $handler->handle((new ListResourcesRequest())->withId(1), $this->createMock(SessionInterface::class))->result;

        $this->assertInstanceOf(ListResourcesResult::class, $result);
        $this->assertCount(1, $result->resources);
        $this->assertSame('dummy://docs', $result->resources[0]->uri);
    }

    /**
     * Reading through the shared registry (rather than a private one) keeps tools registered at
     * runtime — e.g. dynamically discovered affordances — visible in tools/list.
     */
    public function testListToolsIncludesToolsRegisteredAtRuntime(): void
    {
        $registry = new Registry();
        $registry->registerTool(new Tool(name: 'runtime_tool', title: null, inputSchema: ['type' => 'object', 'properties' => [], 'required' => null], description: null, annotations: null), 'runtime_handler');

        $loader = $this->createMock(LoaderInterface::class);
        $handler = new ListHandler($registry, $loader);

        $result = $handler->handle((new ListToolsRequest())->withId(1), $this->createMock(SessionInterface::class))->result;

        $names = array_map(static fn (Tool $t): string => $t->name, $result->tools);
        $this->assertContains('runtime_tool', $names);
    }

    public function testElementsAreLoadedOncePerProcess(): void
    {
        $registry = $this->createMock(RegistryInterface::class);
        $registry->method('getTools')->willReturn(new \Mcp\Schema\Page([], null));

        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->once())->method('load');

        $handler = new ListHandler($registry, $loader);
        $handler->handle((new ListToolsRequest())->withId(1), $this->createMock(SessionInterface::class));
        $handler->handle((new ListToolsRequest())->withId(2), $this->createMock(SessionInterface::class));
    }

    public function testSupportsListRequests(): void
    {
        $handler = new ListHandler($this->createMock(RegistryInterface::class), $this->createMock(LoaderInterface::class));

        $this->assertTrue($handler->supports(new ListToolsRequest()));
        $this->assertTrue($handler->supports(new ListResourcesRequest()));
    }

    private function createLoader(ApiResource $resource, SchemaFactoryInterface $schemaFactory): Loader
    {
        $nameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([\stdClass::class]));

        $metadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [$resource]));

        return new Loader($nameCollectionFactory, $metadataCollectionFactory, $schemaFactory);
    }
}
