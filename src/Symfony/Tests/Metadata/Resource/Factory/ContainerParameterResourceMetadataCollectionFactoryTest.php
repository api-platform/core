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

namespace ApiPlatform\Symfony\Tests\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\Metadata\Resource\Factory\ContainerParameterResourceMetadataCollectionFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ContainerParameterResourceMetadataCollectionFactoryTest extends TestCase
{
    private function createContainer(array $parameters): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(static fn (string $id): bool => \array_key_exists($id, $parameters));
        $container->method('get')->willReturnCallback(static fn (string $id): mixed => $parameters[$id] ?? null);

        return $container;
    }

    private function createFactory(ResourceMetadataCollection $collection, array $parameters): ContainerParameterResourceMetadataCollectionFactory
    {
        $decorated = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn($collection);

        return new ContainerParameterResourceMetadataCollectionFactory($this->createContainer($parameters), $decorated);
    }

    public function testItResolvesWholeStringSecurityExpressionParameter(): void
    {
        $collection = new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withSecurity('%app.security%'),
        ]);

        $factory = $this->createFactory($collection, ['app.security' => 'is_granted("ROLE_ADMIN")']);

        $this->assertSame('is_granted("ROLE_ADMIN")', $factory->create('Foo')[0]->getSecurity());
    }

    public function testItLeavesPlainExpressionUntouched(): void
    {
        $collection = new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withSecurity('is_granted("ROLE_USER")'),
        ]);

        $factory = $this->createFactory($collection, []);

        $this->assertSame('is_granted("ROLE_USER")', $factory->create('Foo')[0]->getSecurity());
    }

    public function testItLeavesModuloExpressionUntouched(): void
    {
        $collection = new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withCondition('object.value % 2 === 0'),
        ]);

        $factory = $this->createFactory($collection, []);

        $this->assertSame('object.value % 2 === 0', $factory->create('Foo')[0]->getCondition());
    }

    public function testItResolvesScalarFieldAnywhereInTheString(): void
    {
        $collection = new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withOperations(new Operations([
                'get' => (new Get())->withRoutePrefix('/%app.prefix%/v1'),
            ])),
        ]);

        $factory = $this->createFactory($collection, ['app.prefix' => 'api']);

        $this->assertSame('/api/v1', $factory->create('Foo')->getOperation('get')->getRoutePrefix());
    }

    public function testItResolvesOperationLevelSecurityExpressionParameter(): void
    {
        $collection = new ResourceMetadataCollection('Foo', [
            (new ApiResource())->withOperations(new Operations([
                'get' => (new Get())->withSecurity('%app.security%'),
            ])),
        ]);

        $factory = $this->createFactory($collection, ['app.security' => 'is_granted("ROLE_ADMIN")']);

        $this->assertSame('is_granted("ROLE_ADMIN")', $factory->create('Foo')->getOperation('get')->getSecurity());
    }
}
