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

namespace ApiPlatform\Mcp\Tests\Metadata\Operation\Factory;

use ApiPlatform\Mcp\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\McpResource;
use ApiPlatform\Metadata\McpTool;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use PHPUnit\Framework\TestCase;

class OperationMetadataFactoryTest extends TestCase
{
    public function testCreateFindsAToolByName(): void
    {
        $mcpTool = new McpTool(name: 'createDummy', class: \stdClass::class);
        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['createDummy' => $mcpTool]);

        $factory = $this->createFactory([\stdClass::class => [$resource]]);

        $this->assertSame($mcpTool, $factory->create('createDummy'));
    }

    public function testCreateFindsAResourceByName(): void
    {
        $mcpResource = new McpResource(uri: 'dummy://docs', name: 'docs', class: \stdClass::class);
        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['docs' => $mcpResource]);

        $factory = $this->createFactory([\stdClass::class => [$resource]]);

        $this->assertSame($mcpResource, $factory->create('docs'));
    }

    /**
     * A `resources/read` request addresses an operation by URI, not by name, so the
     * URI has to resolve too.
     */
    public function testCreateFindsAResourceByUri(): void
    {
        $mcpResource = new McpResource(uri: 'dummy://docs', name: 'docs', class: \stdClass::class);
        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['docs' => $mcpResource]);

        $factory = $this->createFactory([\stdClass::class => [$resource]]);

        $this->assertSame($mcpResource, $factory->create('dummy://docs'));
    }

    /**
     * A tool has no URI, so its name must not be matched against one.
     */
    public function testCreateDoesNotMatchAToolByUri(): void
    {
        $mcpTool = new McpTool(name: 'createDummy', class: \stdClass::class);
        $resource = (new ApiResource(class: \stdClass::class))->withMcp(['createDummy' => $mcpTool]);

        $factory = $this->createFactory([\stdClass::class => [$resource]]);

        $this->assertNull($factory->create('dummy://createDummy'));
    }

    public function testCreateReturnsNullForAnUnknownOperation(): void
    {
        $resource = (new ApiResource(class: \stdClass::class))
            ->withMcp(['createDummy' => new McpTool(name: 'createDummy', class: \stdClass::class)]);

        $factory = $this->createFactory([\stdClass::class => [$resource]]);

        $this->assertNull($factory->create('unknown'));
    }

    public function testCreateSkipsResourcesWithoutMcpOperations(): void
    {
        $factory = $this->createFactory([\stdClass::class => [new ApiResource(class: \stdClass::class)]]);

        $this->assertNull($factory->create('createDummy'));
    }

    /**
     * Only MCP operations are eligible, even when they sit in the `mcp` collection.
     */
    public function testCreateSkipsNonMcpOperations(): void
    {
        $resource = (new ApiResource(class: \stdClass::class))
            ->withMcp(['createDummy' => new Get(name: 'createDummy', class: \stdClass::class)]); // @phpstan-ignore-line an HTTP operation is not an MCP one, which is exactly what is asserted here

        $factory = $this->createFactory([\stdClass::class => [$resource]]);

        $this->assertNull($factory->create('createDummy'));
    }

    public function testCreateLooksThroughEveryResourceClass(): void
    {
        $mcpTool = new McpTool(name: 'createOther', class: \ArrayObject::class);

        $factory = $this->createFactory([
            \stdClass::class => [(new ApiResource(class: \stdClass::class))
                ->withMcp(['createDummy' => new McpTool(name: 'createDummy', class: \stdClass::class)]), ],
            \ArrayObject::class => [(new ApiResource(class: \ArrayObject::class))->withMcp(['createOther' => $mcpTool])],
        ]);

        $this->assertSame($mcpTool, $factory->create('createOther'));
    }

    /**
     * A class may carry several `#[ApiResource]` declarations, each with its own
     * MCP operations.
     */
    public function testCreateLooksThroughEveryResourceOfAClass(): void
    {
        $mcpTool = new McpTool(name: 'createOther', class: \stdClass::class);

        $factory = $this->createFactory([\stdClass::class => [
            (new ApiResource(class: \stdClass::class))
                ->withMcp(['createDummy' => new McpTool(name: 'createDummy', class: \stdClass::class)]),
            (new ApiResource(class: \stdClass::class))->withMcp(['createOther' => $mcpTool]),
        ]]);

        $this->assertSame($mcpTool, $factory->create('createOther'));
    }

    /**
     * @param array<class-string, list<ApiResource>> $resources
     */
    private function createFactory(array $resources): OperationMetadataFactory
    {
        $nameCollectionFactory = $this->createStub(ResourceNameCollectionFactoryInterface::class);
        $nameCollectionFactory->method('create')->willReturn(new ResourceNameCollection(array_keys($resources)));

        $metadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataCollectionFactory->method('create')->willReturnCallback(
            static fn (string $resourceClass): ResourceMetadataCollection => new ResourceMetadataCollection($resourceClass, $resources[$resourceClass] ?? [])
        );

        return new OperationMetadataFactory($nameCollectionFactory, $metadataCollectionFactory);
    }
}
