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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Mutator\OperationMutatorCollection;
use ApiPlatform\Metadata\Mutator\ResourceMutatorCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\OperationMutatorInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\MutatorResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceMutatorInterface;
use PHPUnit\Framework\TestCase;

final class MutatorResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testMutateResource(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClass = \stdClass::class;
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        $resourceMetadataCollection[] = (new ApiResource())->withClass($resourceClass);

        $resourceMutatorCollection = new ResourceMutatorCollection();
        $resourceMutatorCollection->add($resourceClass, new DummyResourceMutator());

        $customResourceMetadataCollectionFactory = new MutatorResourceMetadataCollectionFactory($resourceMutatorCollection, new OperationMutatorCollection(), $decorated);

        $decorated->expects($this->once())->method('create')->with($resourceClass)->willReturn(
            $resourceMetadataCollection,
        );

        $resourceMetadataCollection = $customResourceMetadataCollectionFactory->create($resourceClass);

        $resource = $resourceMetadataCollection->getIterator()->current();
        $this->assertInstanceOf(ApiResource::class, $resource);
        $this->assertSame('dummy', $resource->getShortName());
    }

    public function testMutateOperation(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClass = \stdClass::class;

        $operations = new Operations();
        $operations->add('_api_Dummy_get', new HttpOperation());

        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        $resourceMetadataCollection[] = (new ApiResource())->withClass($resourceClass)->withOperations($operations);

        $operationMutatorCollection = new OperationMutatorCollection();
        $operationMutatorCollection->add('_api_Dummy_get', new DummyOperationMutator());

        $customResourceMetadataCollectionFactory = new MutatorResourceMetadataCollectionFactory(new ResourceMutatorCollection(), $operationMutatorCollection, $decorated);

        $decorated->expects($this->once())->method('create')->with($resourceClass)->willReturn(
            $resourceMetadataCollection,
        );

        $resourceMetadataCollection = $customResourceMetadataCollectionFactory->create($resourceClass);

        $resource = $resourceMetadataCollection->getIterator()->current();
        $this->assertInstanceOf(ApiResource::class, $resource);
        $this->assertEquals('custom_dummy', $resourceMetadataCollection->getOperation('_api_Dummy_get')->getShortName());
    }
}

final class DummyResourceMutator implements ResourceMutatorInterface
{
    public function __invoke(ApiResource $resource): ApiResource
    {
        return $resource->withShortName('dummy');
    }
}

final class DummyOperationMutator implements OperationMutatorInterface
{
    public function __invoke(Operation $operation): Operation
    {
        return $operation->withShortName('custom_dummy');
    }
}
