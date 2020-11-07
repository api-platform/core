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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\ItemDataProvider;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Match as AggregationMatch;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class ItemDataProviderTest extends TestCase
{
    use ProphecyTrait;

    private $resourceMetadataFactoryProphecy;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
    }

    public function testGetItemSingleIdentifier()
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
        $aggregationBuilderProphecy->hydrate(Dummy::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, $aggregationBuilder);

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, Dummy::class, ['id' => 1], 'foo', $context)->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->getItem(Dummy::class, ['id' => 1], 'foo', $context));
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
        $aggregationBuilderProphecy->hydrate(Dummy::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute(['allowDiskUse' => true])->willReturn($iterator->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, $aggregationBuilder);

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(
            'Dummy',
            null,
            null,
            ['foo' => ['doctrine_mongodb' => ['execute_options' => ['allowDiskUse' => true]]]]
        ));

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, Dummy::class, ['id' => 1], 'foo', $context)->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->getItem(Dummy::class, ['id' => 1], 'foo', $context));
    }

    public function testGetItemDoubleIdentifier()
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
        $aggregationBuilderProphecy->hydrate(Dummy::class)->willReturn($aggregationBuilderProphecy)->shouldBeCalled();
        $aggregationBuilderProphecy->execute([])->willReturn($iterator->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'ida',
            'idb',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, $aggregationBuilder);

        $this->resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $context = [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, Dummy::class, ['ida' => 1, 'idb' => 2], 'foo', $context)->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

        $this->assertEquals($result, $dataProvider->getItem(Dummy::class, ['ida' => 1, 'idb' => 2], 'foo', $context));
    }

    /**
     * @group legacy
     */
    public function testGetItemWrongCompositeIdentifier()
    {
        $this->expectException(PropertyNotFoundException::class);

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'ida',
            'idb',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, $this->prophesize(Builder::class)->reveal(), [
            'ida' => [
                'type' => MongoDbType::INTEGER,
            ],
            'idb' => [
                'type' => MongoDbType::INTEGER,
            ],
        ]);

        $dataProvider = new ItemDataProvider($managerRegistry, $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);
        $dataProvider->getItem(Dummy::class, 'ida=1;', 'foo');
    }

    public function testAggregationResultExtension()
    {
        $matchProphecy = $this->prophesize(AggregationMatch::class);
        $matchProphecy->field('id')->willReturn($matchProphecy)->shouldBeCalled();
        $matchProphecy->equals(1)->shouldBeCalled();

        $aggregationBuilderProphecy = $this->prophesize(Builder::class);
        $aggregationBuilderProphecy->match()->willReturn($matchProphecy->reveal())->shouldBeCalled();
        $aggregationBuilder = $aggregationBuilderProphecy->reveal();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);
        $managerRegistry = $this->getManagerRegistry(Dummy::class, $aggregationBuilder);

        $context = [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];
        $extensionProphecy = $this->prophesize(AggregationResultItemExtensionInterface::class);
        $extensionProphecy->applyToItem($aggregationBuilder, Dummy::class, ['id' => 1], 'foo', $context)->shouldBeCalled();
        $extensionProphecy->supportsResult(Dummy::class, 'foo', $context)->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, Dummy::class, 'foo', $context)->willReturn([])->shouldBeCalled();

        $dataProvider = new ItemDataProvider($managerRegistry, $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

        $this->assertEquals([], $dataProvider->getItem(Dummy::class, ['id' => 1], 'foo', $context));
    }

    public function testUnsupportedClass()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);

        $dataProvider = new ItemDataProvider($managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);
        $this->assertFalse($dataProvider->supports(Dummy::class, 'foo'));
    }

    public function testCannotCreateAggregationBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal());

        $extensionProphecy = $this->prophesize(AggregationItemExtensionInterface::class);

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataFactories(Dummy::class, [
            'id',
        ]);

        (new ItemDataProvider($managerRegistryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]))->getItem(Dummy::class, 'foo', null, [IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true]);
    }

    /**
     * Gets mocked metadata factories.
     */
    private function getMetadataFactories(string $resourceClass, array $identifiers): array
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $nameCollection = ['foobar'];

        foreach ($identifiers as $identifier) {
            $metadata = new PropertyMetadata();
            $metadata = $metadata->withIdentifier(true);
            $propertyMetadataFactoryProphecy->create($resourceClass, $identifier)->willReturn($metadata);

            $nameCollection[] = $identifier;
        }

        //random property to prevent the use of non-identifiers metadata while looping
        $propertyMetadataFactoryProphecy->create($resourceClass, 'foobar')->willReturn(new PropertyMetadata());

        $propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection($nameCollection));

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
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

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getRepository($resourceClass)->willReturn($repositoryProphecy->reveal());
        $managerProphecy->getClassMetadata($resourceClass)->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy->reveal();
    }
}
