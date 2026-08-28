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
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
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
use Symfony\Component\ExpressionLanguage\SyntaxError;

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

    public function testListToolsOmitsToolsTheCallerCannotInvoke(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");
        $public = new McpTool(name: 'public', description: 'Public', structuredContent: false, class: \stdClass::class);

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willReturn(false);

        $result = $this->handleListTools([$secured, $public], $accessChecker);

        $this->assertInstanceOf(ListToolsResult::class, $result);
        $this->assertSame(['public'], array_map(static fn (Tool $t): string => $t->name, $result->tools));
    }

    public function testListToolsKeepsToolsTheCallerCanInvoke(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->expects($this->once())->method('isGranted')->with(\stdClass::class, "is_granted('ROLE_ADMIN')")->willReturn(true);

        $result = $this->handleListTools([$secured], $accessChecker);

        $this->assertInstanceOf(ListToolsResult::class, $result);
        $this->assertSame(['secured'], array_map(static fn (Tool $t): string => $t->name, $result->tools));
    }

    /**
     * An expression reading the object (or a uri variable) cannot be evaluated before the tool is
     * called: the tool stays listed and tools/call still enforces the expression.
     */
    public function testListToolsKeepsToolsWhoseExpressionNeedsCallTimeVariables(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: 'object.owner == user');

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willThrowException(new SyntaxError('Variable "object" is not valid'));

        $result = $this->handleListTools([$secured], $accessChecker);

        $this->assertInstanceOf(ListToolsResult::class, $result);
        $this->assertSame(['secured'], array_map(static fn (Tool $t): string => $t->name, $result->tools));
    }

    public function testListResourcesOmitsResourcesTheCallerCannotRead(): void
    {
        $secured = new McpResource(uri: 'dummy://secured', name: 'secured', description: 'Secured', mimeType: 'text/plain', class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");
        $public = new McpResource(uri: 'dummy://public', name: 'public', description: 'Public', mimeType: 'text/plain', class: \stdClass::class);

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willReturn(false);

        $apiResource = (new ApiResource(class: \stdClass::class))->withMcp(['secured' => $secured, 'public' => $public]);
        $handler = new ListHandler(
            new Registry(),
            $this->createLoader($apiResource, $this->createMock(SchemaFactoryInterface::class)),
            20,
            $this->createOperationMetadataFactory([$secured, $public]),
            $accessChecker,
        );

        $result = $handler->handle((new ListResourcesRequest())->withId(1), $this->createMock(SessionInterface::class))->result;

        $this->assertInstanceOf(ListResourcesResult::class, $result);
        $this->assertSame(['dummy://public'], array_map(static fn ($r): string => $r->uri, $result->resources));
    }

    private function createLoader(ApiResource $resource, SchemaFactoryInterface $schemaFactory): Loader
    {
        $nameCollectionFactory = $this->createMock(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection([\stdClass::class]));

        $metadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturn(new ResourceMetadataCollection(\stdClass::class, [$resource]));

        return new Loader($nameCollectionFactory, $metadataCollectionFactory, $schemaFactory);
    }

    /**
     * @param list<McpTool> $tools
     */
    private function handleListTools(array $tools, ResourceAccessCheckerInterface $accessChecker): mixed
    {
        $inputSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($inputSchema['$schema']);
        $inputSchema['type'] = 'object';
        $inputSchema['properties'] = [];

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($inputSchema);

        $mcp = [];
        foreach ($tools as $tool) {
            $mcp[$tool->getName()] = $tool;
        }

        $resource = (new ApiResource(class: \stdClass::class))->withMcp($mcp);

        $handler = new ListHandler(
            new Registry(),
            $this->createLoader($resource, $schemaFactory),
            20,
            $this->createOperationMetadataFactory($tools),
            $accessChecker,
        );

        return $handler->handle((new ListToolsRequest())->withId(1), $this->createMock(SessionInterface::class))->result;
    }

    /**
     * @param list<McpTool|McpResource> $operations
     */
    private function createOperationMetadataFactory(array $operations): OperationMetadataFactoryInterface
    {
        $factory = $this->createMock(OperationMetadataFactoryInterface::class);
        $factory->method('create')->willReturnCallback(static function (string $name) use ($operations) {
            foreach ($operations as $operation) {
                if ($operation->getName() === $name || ($operation instanceof McpResource && $operation->getUri() === $name)) {
                    return $operation;
                }
            }

            return null;
        });

        return $factory;
    }
}
