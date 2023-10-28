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

namespace ApiPlatform\Tests\Doctrine\Odm\State;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\State\CollectionProvider;
use ApiPlatform\Doctrine\Odm\State\Options;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

/**
 * @group mongodb
 */
class CollectionProviderTest extends TestCase
{
    use ExpectDeprecationTrait;
    use ProphecyTrait;

    private ObjectProphecy $managerRegistryProphecy;
    private ObjectProphecy $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
    }

    public function testGetCollection(): void
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(ProviderEntity::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withName('foo')->withClass(ProviderEntity::class);

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, $operation, [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertSame($iterator, $dataProvider->provide($operation, []));
    }

    public function testGetCollectionWithExecuteOptions(): void
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(ProviderEntity::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute(['allowDiskUse' => true])->willReturn($iterator)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withExtraProperties(['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]])->withName('foo')->withClass(ProviderEntity::class);

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, $operation, [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertSame($iterator, $dataProvider->provide($operation, []));
    }

    public function testAggregationResultExtension(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withName('foo')->withClass(ProviderEntity::class);

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, $operation, [])->shouldBeCalled();
        $extensionProphecy->supportsResult(ProviderEntity::class, $operation, [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, ProviderEntity::class, $operation, [])->willReturn([])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide($operation, []));
    }

    public function testCannotCreateAggregationBuilder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal());
        $operation = (new GetCollection())->withName('foo')->withClass(ProviderEntity::class);
        $this->assertEquals([], $dataProvider->provide($operation, []));
    }

    public function testOperationNotFound(): void
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(ProviderEntity::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = new GetCollection(name: 'bar', class: ProviderEntity::class);

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, $operation, [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertSame($iterator, $dataProvider->provide($operation, []));
    }

    /**
     * @group legacy
     */
    public function testHandleLinksCallable(): void
    {
        $this->expectDeprecation('The Doctrine\ODM\MongoDB\Aggregation\Builder::execute method is deprecated (This method was deprecated in doctrine/mongodb-odm 2.2. Please use getAggregation() instead.).');
        $class = 'foo';
        $resourceMetadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $it = $this->createStub(Iterator::class);
        $it->method('current')->willReturn(null);
        $aggregationBuilder = $this->createStub(Builder::class);
        $aggregationBuilder->method('hydrate')->willReturnSelf();
        $aggregationBuilder->method('execute')->willReturn($it);
        $repository = $this->createStub(DocumentRepository::class);
        $repository->method('createAggregationBuilder')->willReturn($aggregationBuilder);
        $manager = $this->createStub(DocumentManager::class);
        $manager->method('getRepository')->willReturn($repository);
        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($manager);
        $operation = new GetCollection(class: $class, stateOptions: new Options(handleLinks: fn () => $this->assertTrue(true)));
        $dataProvider = new CollectionProvider($resourceMetadata, $managerRegistry);
        $dataProvider->provide($operation, ['id' => 1]);
    }
}
