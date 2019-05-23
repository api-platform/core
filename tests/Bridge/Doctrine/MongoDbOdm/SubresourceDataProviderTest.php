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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\SubresourceDataProvider;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\RelatedOwningDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ThirdLevel;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Lookup;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Match;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group mongodb
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SubresourceDataProviderTest extends TestCase
{
    private function getMetadataProphecies(array $resourceClassesIdentifiers)
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        foreach ($resourceClassesIdentifiers as $resourceClass => $identifiers) {
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
        }

        return [$propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal()];
    }

    private function getManagerRegistryProphecy(Builder $aggregationBuilder, array $identifiers, string $resourceClass)
    {
        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->willReturn($aggregationBuilder);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository($resourceClass)->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass($resourceClass)->willReturn($managerProphecy->reveal());

        return $managerRegistryProphecy->reveal();
    }

    public function testNotASubresource()
    {
        $this->expectException(ResourceClassNotSupportedException::class);
        $this->expectExceptionMessage('The given resource class is not a subresource.');

        $identifiers = ['id'];
        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);
        $aggregationBuilder = $this->prophesize(Builder::class)->reveal();
        $managerRegistry = $this->getManagerRegistryProphecy($aggregationBuilder, $identifiers, Dummy::class);

        $dataProvider = new SubresourceDataProvider($managerRegistry, $propertyNameCollectionFactory, $propertyMetadataFactory, []);

        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    public function testGetSubresource()
    {
        $aggregationBuilder = $this->prophesize(Builder::class);

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $dummyAggregationBuilder = $this->prophesize(Builder::class);
        $dummyLookup = $this->prophesize(Lookup::class);
        $dummyLookup->alias('relatedDummies')->shouldBeCalled();
        $dummyAggregationBuilder->lookup('relatedDummies')->shouldBeCalled()->willReturn($dummyLookup->reveal());

        $dummyMatch = $this->prophesize(Match::class);
        $dummyMatch->equals(1)->shouldBeCalled();
        $dummyMatch->field('id')->shouldBeCalled()->willReturn($dummyMatch);
        $dummyAggregationBuilder->match()->shouldBeCalled()->willReturn($dummyMatch->reveal());

        $dummyIterator = $this->prophesize(Iterator::class);
        $dummyIterator->toArray()->shouldBeCalled()->willReturn([['_id' => 1, 'relatedDummies' => [['_id' => 2]]]]);
        $dummyAggregationBuilder->execute()->shouldBeCalled()->willReturn($dummyIterator->reveal());

        $managerProphecy->createAggregationBuilder(Dummy::class)->shouldBeCalled()->willReturn($dummyAggregationBuilder->reveal());

        $match = $this->prophesize(Match::class);
        $match->in([2])->shouldBeCalled();
        $match->field('_id')->shouldBeCalled()->willReturn($match);
        $aggregationBuilder->match()->shouldBeCalled()->willReturn($match);

        $iterator = $this->prophesize(Iterator::class);
        $iterator->toArray()->shouldBeCalled()->willReturn([]);
        $aggregationBuilder->execute()->shouldBeCalled()->willReturn($iterator->reveal());
        $aggregationBuilder->hydrate(RelatedDummy::class)->shouldBeCalled()->willReturn($aggregationBuilder);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => ['id']]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);

        $context = ['property' => 'relatedDummies', 'identifiers' => [['id', Dummy::class]], 'collection' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedDummy::class, ['id' => ['id' => 1]], $context));
    }

    public function testGetSubSubresourceItem()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $identifiers = ['id'];

        // First manager (Dummy)
        $dummyAggregationBuilder = $this->prophesize(Builder::class);
        $dummyLookup = $this->prophesize(Lookup::class);
        $dummyLookup->alias('relatedDummies')->shouldBeCalled();
        $dummyAggregationBuilder->lookup('relatedDummies')->shouldBeCalled()->willReturn($dummyLookup->reveal());

        $dummyMatch = $this->prophesize(Match::class);
        $dummyMatch->equals(1)->shouldBeCalled();
        $dummyMatch->field('id')->shouldBeCalled()->willReturn($dummyMatch);
        $dummyAggregationBuilder->match()->shouldBeCalled()->willReturn($dummyMatch->reveal());

        $dummyIterator = $this->prophesize(Iterator::class);
        $dummyIterator->toArray()->shouldBeCalled()->willReturn([['_id' => 1, 'relatedDummies' => [['_id' => 2]]]]);
        $dummyAggregationBuilder->execute()->shouldBeCalled()->willReturn($dummyIterator->reveal());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();

        $dummyManagerProphecy = $this->prophesize(DocumentManager::class);
        $dummyManagerProphecy->createAggregationBuilder(Dummy::class)->shouldBeCalled()->willReturn($dummyAggregationBuilder->reveal());
        $dummyManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($dummyManagerProphecy->reveal());

        // Second manager (RelatedDummy)
        $rAggregationBuilder = $this->prophesize(Builder::class);
        $rLookup = $this->prophesize(Lookup::class);
        $rLookup->alias('thirdLevel')->shouldBeCalled();
        $rAggregationBuilder->lookup('thirdLevel')->shouldBeCalled()->willReturn($rLookup->reveal());

        $rMatch = $this->prophesize(Match::class);
        $rMatch->equals(1)->shouldBeCalled();
        $rMatch->field('id')->shouldBeCalled()->willReturn($rMatch);
        $previousRMatch = $this->prophesize(Match::class);
        $previousRMatch->in([2])->shouldBeCalled();
        $previousRMatch->field('_id')->shouldBeCalled()->willReturn($previousRMatch);
        $rAggregationBuilder->match()->shouldBeCalled()->willReturn($rMatch->reveal(), $previousRMatch->reveal());

        $rIterator = $this->prophesize(Iterator::class);
        $rIterator->toArray()->shouldBeCalled()->willReturn([['_id' => 1, 'thirdLevel' => [['_id' => 3]]]]);
        $rAggregationBuilder->execute()->shouldBeCalled()->willReturn($rIterator->reveal());

        $rClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $rClassMetadataProphecy->hasAssociation('thirdLevel')->shouldBeCalled()->willReturn(true);

        $rDummyManagerProphecy = $this->prophesize(DocumentManager::class);
        $rDummyManagerProphecy->createAggregationBuilder(RelatedDummy::class)->shouldBeCalled()->willReturn($rAggregationBuilder->reveal());
        $rDummyManagerProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($rClassMetadataProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($rDummyManagerProphecy->reveal());

        $result = new \stdClass();
        // Origin manager (ThirdLevel)
        $aggregationBuilder = $this->prophesize(Builder::class);

        $match = $this->prophesize(Match::class);
        $match->in([3])->shouldBeCalled();
        $match->field('_id')->shouldBeCalled()->willReturn($match);
        $aggregationBuilder->match()->shouldBeCalled()->willReturn($match);

        $iterator = $this->prophesize(Iterator::class);
        $iterator->current()->shouldBeCalled()->willReturn($result);
        $aggregationBuilder->execute()->shouldBeCalled()->willReturn($iterator->reveal());
        $aggregationBuilder->hydrate(ThirdLevel::class)->shouldBeCalled()->willReturn($aggregationBuilder);

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(ThirdLevel::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(ThirdLevel::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers, RelatedDummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);

        $context = ['property' => 'thirdLevel', 'identifiers' => [['id', Dummy::class], ['relatedDummies', RelatedDummy::class]], 'collection' => false, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals($result, $dataProvider->getSubresource(ThirdLevel::class, ['id' => ['id' => 1], 'relatedDummies' => ['id' => 1]], $context));
    }

    public function testGetSubresourceOneToOneOwningRelation()
    {
        // RelatedOwningDummy OneToOne Dummy
        $identifiers = ['id'];
        $aggregationBuilder = $this->prophesize(Builder::class);

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(RelatedOwningDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('ownedDummy')->willReturn(true)->shouldBeCalled();

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $lookup = $this->prophesize(Lookup::class);
        $lookup->alias('ownedDummy')->shouldBeCalled();
        $aggregationBuilder->lookup('ownedDummy')->shouldBeCalled()->willReturn($lookup->reveal());
        $managerProphecy->createAggregationBuilder(Dummy::class)->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $match = $this->prophesize(Match::class);
        $match->equals(1)->shouldBeCalled();
        $match->field('id')->shouldBeCalled()->willReturn($match);
        $previousMatch = $this->prophesize(Match::class);
        $previousMatch->in([3])->shouldBeCalled();
        $previousMatch->field('_id')->shouldBeCalled()->willReturn($previousMatch);
        $aggregationBuilder->match()->shouldBeCalled()->willReturn($match->reveal(), $previousMatch->reveal());

        $iterator = $this->prophesize(Iterator::class);
        $iterator->toArray()->shouldBeCalled()->willReturn([['_id' => 1, 'ownedDummy' => [['_id' => 3]]]]);
        $result = new \stdClass();
        $iterator->current()->shouldBeCalled()->willReturn($result);
        $aggregationBuilder->execute()->shouldBeCalled()->willReturn($iterator->reveal());
        $aggregationBuilder->hydrate(RelatedOwningDummy::class)->shouldBeCalled()->willReturn($aggregationBuilder);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedOwningDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);

        $context = ['property' => 'ownedDummy', 'identifiers' => [['id', Dummy::class]], 'collection' => false, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals($result, $dataProvider->getSubresource(RelatedOwningDummy::class, ['id' => ['id' => 1]], $context));
    }

    public function testAggregationResultExtension()
    {
        $identifiers = ['id'];
        $aggregationBuilder = $this->prophesize(Builder::class);

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->hasAssociation('relatedDummies')->willReturn(true)->shouldBeCalled();

        $managerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $lookup = $this->prophesize(Lookup::class);
        $lookup->alias('relatedDummies')->shouldBeCalled();
        $aggregationBuilder->lookup('relatedDummies')->shouldBeCalled()->willReturn($lookup->reveal());
        $managerProphecy->createAggregationBuilder(Dummy::class)->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $match = $this->prophesize(Match::class);
        $match->equals(1)->shouldBeCalled();
        $match->field('id')->shouldBeCalled()->willReturn($match);
        $previousMatch = $this->prophesize(Match::class);
        $previousMatch->in([3])->shouldBeCalled();
        $previousMatch->field('_id')->shouldBeCalled()->willReturn($previousMatch);
        $aggregationBuilder->match()->shouldBeCalled()->willReturn($match->reveal(), $previousMatch->reveal());

        $iterator = $this->prophesize(Iterator::class);
        $iterator->toArray()->shouldBeCalled()->willReturn([['_id' => 1, 'relatedDummies' => [['_id' => 3]]]]);
        $aggregationBuilder->execute()->shouldBeCalled()->willReturn($iterator->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($managerProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $extensionProphecy = $this->prophesize(AggregationResultCollectionExtensionInterface::class);
        $extensionProphecy->applyToCollection($aggregationBuilder, RelatedDummy::class, null, Argument::type('array'))->shouldBeCalled();
        $extensionProphecy->supportsResult(RelatedDummy::class, null, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $extensionProphecy->getResult($aggregationBuilder, RelatedDummy::class, null, Argument::type('array'))->willReturn([])->shouldBeCalled();

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory, [$extensionProphecy->reveal()]);

        $context = ['property' => 'relatedDummies', 'identifiers' => [['id', Dummy::class]], 'collection' => true, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals([], $dataProvider->getSubresource(RelatedDummy::class, ['id' => ['id' => 1]], $context));
    }

    public function testCannotCreateQueryBuilder()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The repository for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy" must be an instance of "Doctrine\ODM\MongoDB\Repository\DocumentRepository".');

        $identifiers = ['id'];
        $repositoryProphecy = $this->prophesize(ObjectRepository::class);

        $managerProphecy = $this->prophesize(DocumentManager::class);
        $managerProphecy->getRepository(Dummy::class)->willReturn($repositoryProphecy->reveal())->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($managerProphecy->reveal())->shouldBeCalled();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);
        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    public function testThrowResourceClassNotSupportedException()
    {
        $this->expectException(ResourceClassNotSupportedException::class);

        $identifiers = ['id'];
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);
        $dataProvider->getSubresource(Dummy::class, ['id' => 1], []);
    }

    public function testGetSubresourceCollectionItem()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $identifiers = ['id'];

        $rAggregationBuilder = $this->prophesize(Builder::class);

        $rMatch = $this->prophesize(Match::class);
        $rMatch->equals(2)->shouldBeCalled();
        $rMatch->field('id')->shouldBeCalled()->willReturn($rMatch);
        $rAggregationBuilder->match()->shouldBeCalled()->willReturn($rMatch->reveal());

        $rClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $rClassMetadataProphecy->hasAssociation('id')->shouldBeCalled()->willReturn(false);
        $rClassMetadataProphecy->isIdentifier('id')->shouldBeCalled()->willReturn(true);

        $rDummyManagerProphecy = $this->prophesize(DocumentManager::class);
        $rDummyManagerProphecy->createAggregationBuilder(RelatedDummy::class)->shouldBeCalled()->willReturn($rAggregationBuilder->reveal());
        $rDummyManagerProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($rClassMetadataProphecy->reveal());

        $managerRegistryProphecy->getManagerForClass(RelatedDummy::class)->shouldBeCalled()->willReturn($rDummyManagerProphecy->reveal());

        $result = new \stdClass();

        $rIterator = $this->prophesize(Iterator::class);
        $rIterator->current()->shouldBeCalled()->willReturn($result);
        $rAggregationBuilder->execute()->shouldBeCalled()->willReturn($rIterator->reveal());
        $rAggregationBuilder->hydrate(RelatedDummy::class)->shouldBeCalled()->willReturn($rAggregationBuilder);

        $aggregationBuilder = $this->prophesize(Builder::class);

        $repositoryProphecy = $this->prophesize(DocumentRepository::class);
        $repositoryProphecy->createAggregationBuilder()->shouldBeCalled()->willReturn($aggregationBuilder->reveal());

        $rDummyManagerProphecy->getRepository(RelatedDummy::class)->shouldBeCalled()->willReturn($repositoryProphecy->reveal());

        [$propertyNameCollectionFactory, $propertyMetadataFactory] = $this->getMetadataProphecies([Dummy::class => $identifiers, RelatedDummy::class => $identifiers]);

        $dataProvider = new SubresourceDataProvider($managerRegistryProphecy->reveal(), $propertyNameCollectionFactory, $propertyMetadataFactory);

        $context = ['property' => 'id', 'identifiers' => [['id', Dummy::class, true], ['relatedDummies', RelatedDummy::class, true]], 'collection' => false, IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER => true];

        $this->assertEquals($result, $dataProvider->getSubresource(RelatedDummy::class, ['id' => ['id' => 1], 'relatedDummies' => ['id' => 2]], $context));
    }
}
