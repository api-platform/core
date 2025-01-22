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

namespace ApiPlatform\Doctrine\Odm\Tests\State;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\State\CollectionProvider;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\ProviderDocument;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\IterableResult;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class CollectionProviderTest extends TestCase
{
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

        $aggregationProphecy = $this->prophesize(IterableResult::class);
        $aggregationProphecy->getIterator()->willReturn($iterator);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(ProviderDocument::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->getAggregation([])->willReturn($aggregationProphecy)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderDocument::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderDocument::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withName('foo')->withClass(ProviderDocument::class);

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderDocument::class, $operation, [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertSame($iterator, $dataProvider->provide($operation, []));
    }

    public function testGetCollectionWithExecuteOptions(): void
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationProphecy = $this->prophesize(IterableResult::class);
        $aggregationProphecy->getIterator()->willReturn($iterator);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(ProviderDocument::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->getAggregation(['allowDiskUse' => true])->willReturn($aggregationProphecy)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderDocument::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderDocument::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withExtraProperties(['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]])->withName('foo')->withClass(ProviderDocument::class);

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderDocument::class, $operation, [])->shouldBeCalled();

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
        $managerProphecy->getRepository(ProviderDocument::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderDocument::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = (new GetCollection())->withName('foo')->withClass(ProviderDocument::class);

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderDocument::class, $operation, [])->shouldBeCalled();
        $extensionProphecy->supportsResult(ProviderDocument::class, $operation, [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, ProviderDocument::class, $operation, [])->willReturn([])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->provide($operation, []));
    }

    public function testCannotCreateAggregationBuilder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\ProviderDocument" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderDocument::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderDocument::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal());
        $operation = (new GetCollection())->withName('foo')->withClass(ProviderDocument::class);
        $this->assertEquals([], $dataProvider->provide($operation, []));
    }

    public function testOperationNotFound(): void
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationProphecy = $this->prophesize(IterableResult::class);
        $aggregationProphecy->getIterator()->willReturn($iterator);

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(ProviderDocument::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->getAggregation([])->willReturn($aggregationProphecy)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ProviderDocument::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(ProviderDocument::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $operation = new GetCollection(name: 'bar', class: ProviderDocument::class);

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, ProviderDocument::class, $operation, [])->shouldBeCalled();

        $dataProvider = new CollectionProvider($this->resourceMetadataFactoryProphecy->reveal(), $this->managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertSame($iterator, $dataProvider->provide($operation, []));
    }
}
