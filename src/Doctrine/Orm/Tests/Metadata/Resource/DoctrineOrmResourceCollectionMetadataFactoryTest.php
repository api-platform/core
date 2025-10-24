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

namespace ApiPlatform\Doctrine\Orm\Tests\Metadata\Resource;

use ApiPlatform\Doctrine\Orm\Metadata\Resource\DoctrineOrmResourceCollectionMetadataFactory;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\Dummy;
use ApiPlatform\Doctrine\Orm\Tests\Fixtures\Entity\DummyReadOnly;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DoctrineOrmResourceCollectionMetadataFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function getResourceMetadataCollectionFactory(HttpOperation $operation)
    {
        $resourceMetadataCollectionFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->create($operation->getClass())->willReturn(new ResourceMetadataCollection($operation->getClass(), [
            (new ApiResource())
                ->withOperations(
                    new Operations([$operation->getName() => $operation])
                )->withGraphQlOperations([
                    'graphql_'.$operation->getName() => $operation->withName('graphql_'.$operation->getName()),
                ]),
        ]));

        return $resourceMetadataCollectionFactory->reveal();
    }

    public function testWithoutManager(): void
    {
        $operation = (new Get())->withClass(Dummy::class)->withName('get');
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Dummy::class)->willReturn(null);

        $resourceMetadataCollectionFactory = new DoctrineOrmResourceCollectionMetadataFactory($managerRegistry->reveal(), $this->getResourceMetadataCollectionFactory($operation));
        $resourceMetadataCollection = $resourceMetadataCollectionFactory->create(Dummy::class);

        $this->assertNull($resourceMetadataCollection->getOperation('get')->getProvider());
        $this->assertNull($resourceMetadataCollection->getOperation('graphql_get')->getProvider());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('operationProvider')]
    public function testWithProvider(HttpOperation $operation, ?string $expectedProvider = null, ?string $expectedProcessor = null): void
    {
        $objectManager = $this->prophesize(EntityManagerInterface::class);
        $objectManager->getClassMetadata($operation->getClass())->willReturn(new ClassMetadata(Dummy::class));
        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass($operation->getClass())->willReturn($objectManager->reveal());
        $resourceMetadataCollectionFactory = new DoctrineOrmResourceCollectionMetadataFactory($managerRegistry->reveal(), $this->getResourceMetadataCollectionFactory($operation));
        $resourceMetadataCollection = $resourceMetadataCollectionFactory->create($operation->getClass());
        $this->assertSame($expectedProvider, $resourceMetadataCollection->getOperation($operation->getName())->getProvider());
        $this->assertSame($expectedProvider, $resourceMetadataCollection->getOperation('graphql_'.$operation->getName())->getProvider());
        $this->assertSame($expectedProcessor, $resourceMetadataCollection->getOperation($operation->getName())->getProcessor());
        $this->assertSame($expectedProcessor, $resourceMetadataCollection->getOperation('graphql_'.$operation->getName())->getProcessor());
    }

    public static function operationProvider(): iterable
    {
        $default = (new Get())->withName('get')->withClass(Dummy::class);

        yield [(new Get())->withProvider('has a provider')->withProcessor('and a processor')->withOperation($default), 'has a provider', 'and a processor'];
        yield [(new Get())->withOperation($default), ItemProvider::class, 'api_platform.doctrine.orm.state.persist_processor'];
        yield [(new GetCollection())->withOperation($default), CollectionProvider::class, 'api_platform.doctrine.orm.state.persist_processor'];
        yield [(new Delete())->withOperation($default), ItemProvider::class, 'api_platform.doctrine.orm.state.remove_processor'];
    }

    public function testReadOnlyEntitiesShouldNotIncludeUpdateOperations(): void
    {
        $objectManager = $this->createMock(EntityManagerInterface::class);
        $readOnlyMetadata = new ClassMetadata(DummyReadOnly::class);
        $readOnlyMetadata->markReadOnly();
        $objectManager->method('getClassMetadata')->willReturn($readOnlyMetadata);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->with(DummyReadOnly::class)->willReturn($objectManager);

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory
            ->method('create')
            ->with(DummyReadOnly::class)
            ->willReturn(new ResourceMetadataCollection(DummyReadOnly::class, [
                (new ApiResource())
                    ->withOperations(
                        new Operations([
                            'get' => (new Get())->withClass(DummyReadOnly::class),
                            'get_collection' => (new GetCollection())->withClass(DummyReadOnly::class),
                            'post' => (new Post())->withClass(DummyReadOnly::class),
                            'put' => (new Put())->withClass(DummyReadOnly::class),
                            'patch' => (new Patch())->withClass(DummyReadOnly::class),
                            'delete' => (new Delete())->withClass(DummyReadOnly::class),
                        ])
                    ),
            ]));

        $resourceMetadataCollectionFactory = new DoctrineOrmResourceCollectionMetadataFactory($managerRegistry, $resourceMetadataCollectionFactory);

        $resourceMetadataCollection = $resourceMetadataCollectionFactory->create(DummyReadOnly::class);
        /** @var ApiResource $apiResource */
        $apiResource = $resourceMetadataCollection->getIterator()->current();
        $operations = $apiResource->getOperations();
        $this->assertNotNull($operations);
        $this->assertTrue($operations->has('get'));
        $this->assertTrue($operations->has('get_collection'));
        $this->assertTrue($operations->has('post'));
        $this->assertFalse($operations->has('put'));
        $this->assertFalse($operations->has('path'));
        $this->assertTrue($operations->has('delete'));
    }
}
