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

namespace ApiPlatform\GraphQl\Tests\Metadata;

use ApiPlatform\GraphQl\Metadata\RuntimeOperationMetadataFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

class RuntimeOperationMetadataFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $resourceClass = 'Dummy';
        $operationName = 'item_query';

        $operation = (new Query())->withName($operationName);
        $resourceMetadata = (new ApiResource())->withGraphQlOperations([$operationName => $operation]);
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass, [$resourceMetadata]);

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())
            ->method('create')
            ->with($resourceClass)
            ->willReturn($resourceMetadataCollection);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->with('/dummies/1')
            ->willReturn([
                '_api_resource_class' => $resourceClass,
                '_api_operation_name' => $operationName,
            ]);

        $factory = new RuntimeOperationMetadataFactory($resourceMetadataCollectionFactory, $router);
        $this->assertEquals($operation, $factory->create('/dummies/1'));
    }

    public function testCreateThrowsExceptionWhenRouteNotFound(): void
    {
        $this->expectException(\ApiPlatform\Metadata\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('No route matches "/unknown".');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->with('/unknown')
            ->willThrowException(new ResourceNotFoundException());

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);

        $factory = new RuntimeOperationMetadataFactory($resourceMetadataCollectionFactory, $router);
        $factory->create('/unknown');
    }

    public function testCreateThrowsExceptionWhenResourceClassMissing(): void
    {
        $this->expectException(\ApiPlatform\Metadata\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The route "/dummies/1" is not an API route, it has no resource class in the defaults.');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->with('/dummies/1')
            ->willReturn([]);

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);

        $factory = new RuntimeOperationMetadataFactory($resourceMetadataCollectionFactory, $router);
        $factory->create('/dummies/1');
    }

    public function testCreateThrowsExceptionWhenOperationNotFound(): void
    {
        $this->expectException(\ApiPlatform\Metadata\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('No operation found for id "/dummies/1".');

        $resourceClass = 'Dummy';

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())
            ->method('create')
            ->with($resourceClass)
            ->willReturn(new ResourceMetadataCollection($resourceClass, [new ApiResource()]));

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->with('/dummies/1')
            ->willReturn([
                '_api_resource_class' => $resourceClass,
            ]);

        $factory = new RuntimeOperationMetadataFactory($resourceMetadataCollectionFactory, $router);
        $factory->create('/dummies/1');
    }

    public function testCreateIgnoresOperationsWithResolvers(): void
    {
        $this->expectException(\ApiPlatform\Metadata\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('No operation found for id "/dummies/1".');

        $resourceClass = 'Dummy';
        $operationName = 'item_query';

        $operation = (new Query())->withResolver('t')->withName($operationName);
        $resourceMetadata = (new ApiResource())->withGraphQlOperations([$operationName => $operation]);
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass, [$resourceMetadata]);

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())
            ->method('create')
            ->with($resourceClass)
            ->willReturn($resourceMetadataCollection);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->with('/dummies/1')
            ->willReturn([
                '_api_resource_class' => $resourceClass,
                '_api_operation_name' => $operationName,
            ]);

        $factory = new RuntimeOperationMetadataFactory($resourceMetadataCollectionFactory, $router);
        $factory->create('/dummies/1');
    }
}
