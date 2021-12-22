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

use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\State\ItemProvider;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage as AggregationMatch;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
class ItemProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testGetItemSingleIdentifier()
    {
        $context = ['foo' => 'bar', 'fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $matchProphecy = $this->prophesize(AggregationMatch::class);
        $matchProphecy->field('id')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->equals(1)->shouldBeCalled();

        $iterator = $this->prophesize(Iterator::class);
        $result = new \stdClass();
        $iterator->current()->willReturn($result)->shouldBeCalled();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->match()->willReturn($matchProphecy->reveal())->shouldBeCalled();
        $aggregationBuilderProphecy->hydrate(ProviderEntity::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $managerRegistry = $this->getManagerRegistry(ProviderEntity::class, $aggregationBuilder);

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['id' => 1], 'foo', $context)->shouldBeCalled();

        $resourceMetadataFactory->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => (new Get())->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['id'])])]))]));

        $dataProvider = new ItemProvider($resourceMetadataFactory->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->provide(ProviderEntity::class, ['id' => 1], 'foo', $context));
    }

    public function testGetItemWithExecuteOptions()
    {
        $context = ['foo' => 'bar', 'fetch_data' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $matchProphecy = $this->prophesize(AggregationMatch::class);
        $matchProphecy->field('id')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->equals(1)->shouldBeCalled();

        $iterator = $this->prophesize(Iterator::class);
        $result = new \stdClass();
        $iterator->current()->willReturn($result)->shouldBeCalled();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->match()->willReturn($matchProphecy->reveal())->shouldBeCalled();
        $aggregationBuilderProphecy->hydrate(ProviderEntity::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute(['allowDiskUse' => true])->willReturn($iterator->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $managerRegistry = $this->getManagerRegistry(ProviderEntity::class, $aggregationBuilder);

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [
            (new ApiResource())->withOperations(new Operations(['foo' => (new Get())->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['id'])])->withExtraProperties(['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]])])),
        ]));

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['id' => 1], 'foo', $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($resourceMetadataFactory->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->provide(ProviderEntity::class, ['id' => 1], 'foo', $context));
    }

    public function testGetItemDoubleIdentifier()
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $matchProphecy = $this->prophesize(AggregationMatch::class);
        $matchProphecy->field('ida')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->field('idb')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->equals(1)->shouldBeCalled();
        $matchProphecy->equals(2)->shouldBeCalled();

        $iterator = $this->prophesize(Iterator::class);
        $result = new \stdClass();
        $iterator->current()->willReturn($result)->shouldBeCalled();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->match()->willReturn($matchProphecy->reveal())->shouldBeCalled();
        $aggregationBuilderProphecy->hydrate(ProviderEntity::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $managerRegistry = $this->getManagerRegistry(ProviderEntity::class, $aggregationBuilder);

        $context = [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['ida' => 1, 'idb' => 2], 'foo', $context)->shouldBeCalled();

        $resourceMetadataFactory->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => (new Get())->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['ida', 'idb'])])]))]));

        $dataProvider = new ItemProvider($resourceMetadataFactory->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->provide(ProviderEntity::class, ['ida' => 1, 'idb' => 2], 'foo', $context));
    }

    public function testAggregationResultExtension()
    {
        $matchProphecy = $this->prophesize(AggregationMatch::class);
        $matchProphecy->field('id')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->equals(1)->shouldBeCalled();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->match()->willReturn($matchProphecy->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $managerRegistry = $this->getManagerRegistry(ProviderEntity::class, $aggregationBuilder);

        $context = [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $extensionProphecy = $this->prophesize(AggregationResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['id' => 1], 'foo', $context)->shouldBeCalled();
        $extensionProphecy->supportsResult(ProviderEntity::class, 'foo', $context)->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, ProviderEntity::class, 'foo', $context)->willReturn([])->shouldBeCalled();
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $resourceMetadataFactory->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => (new Get())->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['id'])])]))]));

        $dataProvider = new ItemProvider($resourceMetadataFactory->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->provide(ProviderEntity::class, ['id' => 1], 'foo', $context));
    }

    public function testSupportedClass()
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $documentManagerProphecy = $this->prophesize(DocumentManager::class);

        $resourceMetadataFactory->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => new Get()]))]));

        $managerProphecy = $this->prophesize(ManagerRegistry::class);

        $managerProphecy->getManagerForClass(ProviderEntity::class)->willReturn($documentManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        $dataProvider = new ItemProvider($resourceMetadataFactory->reveal(), $managerProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertTrue($dataProvider->supports(ProviderEntity::class, [], 'foo'));
    }

    public function testNotItemOperation()
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $documentManagerProphecy = $this->prophesize(DocumentManager::class);

        $resourceMetadataFactory->create(ProviderEntity::class)->willReturn(new ResourceMetadataCollection(ProviderEntity::class, [(new ApiResource())->withOperations(new Operations(['foo' => new GetCollection()]))]));

        $managerProphecy = $this->prophesize(ManagerRegistry::class);

        $managerProphecy->getManagerForClass(ProviderEntity::class)->willReturn($documentManagerProphecy->reveal())->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        $dataProvider = new ItemProvider($resourceMetadataFactory->reveal(), $managerProphecy->reveal(), [$extensionProphecy->reveal()]);

        $this->assertFalse($dataProvider->supports(ProviderEntity::class, [], 'foo'));
    }

    public function testUnsupportedClass()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal();

        $dataProvider = new ItemProvider($resourceMetadataFactory, $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class, [], 'foo'));
    }

    public function testCannotCreateAggregationBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal());

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal();

        (new ItemProvider($resourceMetadataFactory, $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]))->provide(ProviderEntity::class, [], 'foo', [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true]);
    }

    /**
     * Gets a mocked manager registry.
     */
    private function getManagerRegistry(string $resourceClass, Builder $aggregationBuilder, array $identifierFields = []): ManagerRegistry
    {
        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(array_keys($identifierFields));

        foreach ($identifierFields as $name => $field) {
            $classMetadataProphecy->getTypeOfField($name)->willReturn($field['type']);
        }

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository($resourceClass)->willReturn($repositoryProphecy->reveal());
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy->reveal();
    }
}
