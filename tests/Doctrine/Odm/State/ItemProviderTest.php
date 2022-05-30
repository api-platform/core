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

use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\State\ItemProvider;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity;
use ApiPlatform\Tests\ProphecyTrait;
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

    public function testGetItemSingleIdentifier(): void
    {
        $context = ['foo' => 'bar', 'fetch_data' => true];

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

        $operation = (new Get())
            ->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['id'])])
            ->withClass(ProviderEntity::class)
            ->withName('foo');

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['id' => 1], $operation, $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->provide($operation, ['id' => 1], $context));
    }

    public function testGetItemWithExecuteOptions(): void
    {
        $context = ['foo' => 'bar', 'fetch_data' => true];

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

        $operation = (new Get())
            ->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['id'])])
            ->withClass(ProviderEntity::class)
            ->withName('foo')
            ->withExtraProperties(['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]]);

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['id' => 1], $operation, $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->provide($operation, ['id' => 1], $context));
    }

    public function testGetItemDoubleIdentifier(): void
    {
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

        $operation = (new Get())
            ->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['ida', 'idb'])])
            ->withClass(ProviderEntity::class)
            ->withName('foo');

        $context = [];
        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['ida' => 1, 'idb' => 2], $operation, $context)->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->provide($operation, ['ida' => 1, 'idb' => 2], $context));
    }

    public function testAggregationResultExtension(): void
    {
        $matchProphecy = $this->prophesize(AggregationMatch::class);
        $matchProphecy->field('id')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->equals(1)->shouldBeCalled();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->match()->willReturn($matchProphecy->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        $managerRegistry = $this->getManagerRegistry(ProviderEntity::class, $aggregationBuilder);

        $operation = (new Get())
            ->withUriVariables([(new Link())->withFromClass(ProviderEntity::class)->withIdentifiers(['id'])])
            ->withClass(ProviderEntity::class)
            ->withName('foo');

        $context = [];
        $extensionProphecy = $this->prophesize(AggregationResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, ProviderEntity::class, ['id' => 1], $operation, $context)->shouldBeCalled();
        $extensionProphecy->supportsResult(ProviderEntity::class, $operation, $context)->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, ProviderEntity::class, $operation, $context)->willReturn([])->shouldBeCalled();

        $dataProvider = new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistry, [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->provide($operation, ['id' => 1], $context));
    }

    public function testCannotCreateAggregationBuilder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Tests\Fixtures\TestBundle\Document\ProviderEntity" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(ProviderEntity::class)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(ProviderEntity::class)->willReturn($managerProphecy->reveal());

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        (new ItemProvider($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $managerRegistryProphecy->reveal(), [$extensionProphecy->reveal()]))->provide((new Get())->withClass(ProviderEntity::class), [], []);
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
