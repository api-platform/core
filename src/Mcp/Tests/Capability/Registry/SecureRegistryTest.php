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
use ApiPlatform\Mcp\Capability\Registry\SecureRegistry;
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
use Mcp\Schema\Page;
use Mcp\Schema\Tool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class SecureRegistryTest extends TestCase
{
    public function testToolsAreLoadedIntoTheRegistryOnFirstRead(): void
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

        $registry = new SecureRegistry(new Registry(), $this->createLoader($resource, $schemaFactory));

        $page = $registry->getTools();

        $this->assertSame(['search'], array_map(static fn (Tool $tool): string => $tool->name, array_values($page->references)));
    }

    public function testResourcesAreLoadedIntoTheRegistryOnFirstRead(): void
    {
        $mcpResource = new McpResource(
            uri: 'dummy://docs',
            name: 'docs',
            description: 'Documentation resource',
            mimeType: 'text/plain',
            class: \stdClass::class,
        );

        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['docs' => $mcpResource]);

        $registry = new SecureRegistry(new Registry(), $this->createLoader($resource, $this->createMock(SchemaFactoryInterface::class)));

        $page = $registry->getResources();

        $this->assertSame(['dummy://docs'], array_column($page->references, 'uri'));
    }

    public function testToolRegisteredAtRuntimeIsReturned(): void
    {
        $inner = new Registry();
        $inner->registerTool(new Tool(name: 'runtime_tool', title: null, inputSchema: ['type' => 'object', 'properties' => [], 'required' => null], description: null, annotations: null), 'runtime_handler');

        $registry = new SecureRegistry($inner, $this->createMock(LoaderInterface::class));

        $page = $registry->getTools();

        $names = array_map(static fn (Tool $tool): string => $tool->name, array_values($page->references));
        $this->assertContains('runtime_tool', $names);
    }

    public function testElementsAreLoadedExactlyOnce(): void
    {
        $inner = $this->createMock(RegistryInterface::class);
        $inner->method('getTools')->willReturn(new Page([], null));

        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->once())->method('load');

        $registry = new SecureRegistry($inner, $loader);
        $registry->getTools();
        $registry->getTools();
    }

    public function testToolDeniedBySecurityIsOmittedFromGetTools(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");
        $public = new McpTool(name: 'public', description: 'Public', structuredContent: false, class: \stdClass::class);

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willReturn(false);

        $page = $this->buildToolRegistry([$secured, $public], $accessChecker)->getTools();

        $this->assertSame(['public'], array_map(static fn (Tool $tool): string => $tool->name, array_values($page->references)));
    }

    public function testToolGrantedBySecurityIsKept(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->expects($this->once())->method('isGranted')->with(\stdClass::class, "is_granted('ROLE_ADMIN')")->willReturn(true);

        $page = $this->buildToolRegistry([$secured], $accessChecker)->getTools();

        $this->assertSame(['secured'], array_map(static fn (Tool $tool): string => $tool->name, array_values($page->references)));
    }

    public function testToolWithCallTimeSecurityExpressionStaysListed(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: 'object.owner == user');

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willThrowException(new SyntaxError('Variable "object" is not valid'));

        $page = $this->buildToolRegistry([$secured], $accessChecker)->getTools();

        $this->assertSame(['secured'], array_map(static fn (Tool $tool): string => $tool->name, array_values($page->references)));
    }

    public function testResourceDeniedBySecurityIsOmittedFromGetResources(): void
    {
        $secured = new McpResource(uri: 'dummy://secured', name: 'secured', description: 'Secured', mimeType: 'text/plain', class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");
        $public = new McpResource(uri: 'dummy://public', name: 'public', description: 'Public', mimeType: 'text/plain', class: \stdClass::class);

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willReturn(false);

        $apiResource = (new ApiResource(class: \stdClass::class))->withMcp(['secured' => $secured, 'public' => $public]);
        $registry = new SecureRegistry(
            new Registry(),
            $this->createLoader($apiResource, $this->createMock(SchemaFactoryInterface::class)),
            $this->createOperationMetadataFactory([$secured, $public]),
            $accessChecker,
        );

        $page = $registry->getResources();

        $this->assertSame(['dummy://public'], array_column($page->references, 'uri'));
    }

    public function testNoFilteringWhenMetadataFactoryAndAccessCheckerAreNull(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $inputSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($inputSchema['$schema']);
        $inputSchema['type'] = 'object';
        $inputSchema['properties'] = [];

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($inputSchema);

        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['secured' => $secured]);

        $registry = new SecureRegistry(new Registry(), $this->createLoader($resource, $schemaFactory));

        $page = $registry->getTools();

        $this->assertSame(['secured'], array_map(static fn (Tool $tool): string => $tool->name, array_values($page->references)));
    }

    public function testGetToolStillReturnsReferenceForToolDeniedBySecurity(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willReturn(false);

        $reference = $this->buildToolRegistry([$secured], $accessChecker)->getTool('secured');

        $this->assertSame('secured', $reference->tool->name);
    }

    public function testHasToolsIsTrueEvenWhenEveryToolIsDenied(): void
    {
        $secured = new McpTool(name: 'secured', description: 'Secured', structuredContent: false, class: \stdClass::class, security: "is_granted('ROLE_ADMIN')");

        $accessChecker = $this->createMock(ResourceAccessCheckerInterface::class);
        $accessChecker->method('isGranted')->willReturn(false);

        $this->assertTrue($this->buildToolRegistry([$secured], $accessChecker)->hasTools());
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
    private function buildToolRegistry(array $tools, ResourceAccessCheckerInterface $accessChecker): SecureRegistry
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

        return new SecureRegistry(
            new Registry(),
            $this->createLoader($resource, $schemaFactory),
            $this->createOperationMetadataFactory($tools),
            $accessChecker,
        );
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
