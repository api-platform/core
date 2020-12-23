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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\CollectionDataProvider;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class CollectionDataProviderTest extends TestCase
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
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
    }

    public function testGetCollection()
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(Dummy::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, Dummy::class, 'foo', [])->shouldBeCalled();

        $dataProvider = new CollectionDataProvider($this->managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals($iterator, $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testGetCollectionWithExecuteOptions()
    {
        $iterator = $this->prophesize(Iterator::class)->reveal();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->hydrate(Dummy::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute(['allowDiskUse' => true])->willReturn($iterator)->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(
            'Dummy',
            null,
            null,
            null,
            ['foo' => ['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]]]
        ));

        $extensionProphecy = $this->prophesize(AggregationCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, Dummy::class, 'foo', [])->shouldBeCalled();

        $dataProvider = new CollectionDataProvider($this->managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals($iterator, $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testAggregationResultExtension()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder)->shouldBeCalled();

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, Dummy::class, 'foo', [])->shouldBeCalled();
        $extensionProphecy->supportsResult(Dummy::class, 'foo', [])->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, Dummy::class, 'foo', [])->willReturn([])->shouldBeCalled();

        $dataProvider = new CollectionDataProvider($this->managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testCannotCreateAggregationBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        $dataProvider = new CollectionDataProvider($this->managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal());
        $this->assertEquals([], $dataProvider->getCollection(Dummy::class, 'foo'));
    }

    public function testUnsupportedClass()
    {
        $this->managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);

        $dataProvider = new CollectionDataProvider($this->managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class, 'foo'));
    }
}
