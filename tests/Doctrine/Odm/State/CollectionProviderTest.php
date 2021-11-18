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

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\State\CollectionProvider;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
class CollectionProviderTest extends TestCase
{
    use ProphecyTrait;

    private $managerRegistryProphecy;
    private $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
    }

    public function testGetCollection()
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

        $this->resourceMetadataFactoryProphecy->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => new GetCollection()]))]));

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, 'foo', [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals($iterator, $dataProvider->provide(ProviderEntity::class, [], 'foo'));
    }

    public function testGetCollectionWithExecuteOptions()
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

        $this->resourceMetadataFactoryProphecy->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => (new GetCollection())->withExtraProperties(['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]])]))]));

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, 'foo', [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals($iterator, $dataProvider->provide(ProviderEntity::class, [], 'foo'));
    }

    public function testAggregationResultExtension()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, 'foo', [])->shouldBeCalled();
        $extensionProphecy->supportsResult(ProviderEntity::class, 'foo', [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, ProviderEntity::class, 'foo', [])->willReturn([])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide(ProviderEntity::class, [], 'foo'));
    }

    public function testCannotCreateAggregationBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal());
        $this->assertEquals([], $dataProvider->provide(ProviderEntity::class, [], 'foo'));
    }

    public function testSupportedClass()
    {
        $documentManagerProphecy = $this->prophesize(DocumentManager::class);

        $this->resourceMetadataFactoryProphecy->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => new GetCollection()]))]));
        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($documentManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertTrue($dataProvider->supports(ProviderEntity::class, [], 'foo'));
    }

    public function testUnsupportedClass()
    {
        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class, [], 'foo'));
    }

    public function testNotCollectionOperation()
    {
        $documentManagerProphecy = $this->prophesize(DocumentManager::class);

        $this->resourceMetadataFactoryProphecy->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => new Get()]))]));
        $this->managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($documentManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(ProviderEntity::class, [], 'foo'));
    }

    public function testOperationNotFound()
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

        $this->resourceMetadataFactoryProphecy->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => new GetCollection()]))]));

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderEntity::class, 'bar', [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals($iterator, $dataProvider->provide(ProviderEntity::class, [], 'bar'));
    }
}
