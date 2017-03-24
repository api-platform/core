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

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UnknownDummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
class EagerLoadingExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testApplyToCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new PropertyMetadata();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new PropertyMetadata();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', [])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', [])->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase', [])->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable', [])->willReturn($notReadablePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ($property !== 'id') {
                $relatedClassMetadataProphecy->hasField($property)->willReturn($property !== 'notindatabase')->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a2')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy2_a2.{id,name}')->shouldBeCalled(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToItem()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable', 'relation']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy3', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy4', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy5', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new PropertyMetadata();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new PropertyMetadata();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id', [])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name', [])->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase', [])->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable', [])->willReturn($notReadablePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'relation', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id', [])->willReturn($idPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy3' => ['fetch' => 3, 'joinTable' => ['joinColumns' => [['nullable' => false]]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy4' => ['fetch' => 3, 'targetEntity' => UnknownDummy::class],
            'relatedDummy5' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);

        foreach ($relatedNameCollection as $property) {
            if ($property !== 'id') {
                $relatedClassMetadataProphecy->hasField($property)->willReturn($property !== 'notindatabase')->shouldBeCalled();
            }
        }

        $relatedClassMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'relatedDummy_a1')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('relatedDummy_a1.relation', 'relation_a2')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'relatedDummy2_a3')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('o.relatedDummy3', 'relatedDummy3_a4')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('o.relatedDummy4', 'relatedDummy4_a5')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy_a1.{id,name}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relation_a2.{id}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy2_a3.{id}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy3_a4.{id}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relatedDummy4_a5.{id}')->shouldBeCalled(1);

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);

        $orderExtensionTest->applyToItem($queryBuilder, new QueryNameGenerator(), Dummy::class, []);
    }

    public function testCreateItemWithOperationName()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['item_operation_name' => 'item_operation'])->shouldBeCalled()->willReturn(new PropertyMetadata());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], 'item_operation');
    }

    public function testCreateCollectionWithOperationName()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['collection_operation_name' => 'collection_operation'])->shouldBeCalled()->willReturn(new PropertyMetadata());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, 'collection_operation');
    }

    public function testDenormalizeItemWithCorrectResourceClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        //Dummy is the correct class for the denormalization context serialization groups, and we're fetching RelatedDummy
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], 'item_operation', ['resource_class' => Dummy::class]);
    }

    public function testDenormalizeItemWithExistingGroups()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        //groups exist from the context, we don't need to compute them again
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], 'item_operation', ['groups' => 'some_groups']);
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\RuntimeException
     * @expectedExceptionMessage The total number of joined relations has exceeded the specified maximum. Raise the limit if necessary.
     */
    public function testMaxDepthReached()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['dummy']);
        $dummyNameCollection = new PropertyNameCollection(['relatedDummy']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(Dummy::class)->willReturn($dummyNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $relatedPropertyMetadata = new PropertyMetadata();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'dummy', [])->willReturn($relatedPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [
            'dummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => Dummy::class],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $queryBuilderProphecy->innerJoin(Argument::type('string'), Argument::type('string'))->shouldBeCalled();
        $queryBuilderProphecy->addSelect(Argument::type('string'))->shouldBeCalled();

        $eagerExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class);
    }

    public function testForceEager()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);

        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id', [])->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy->innerJoin('o.relation', 'relation_a1')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial relation_a1.{id}')->shouldBeCalled(1);

        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testResourceClassNotFoundException()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', [])->willThrow(new ResourceClassNotFoundException());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testPropertyNotFoundException()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', [])->willThrow(new PropertyNotFoundException());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testResourceClassNotFoundExceptionPropertyNameCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willThrow(new ResourceClassNotFoundException());

        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', [])->willReturn($relationPropertyMetadata);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];
        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy);
        $queryBuilderProphecy->innerJoin('o.relation', 'relation_a1')->shouldBeCalled(1);

        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }
}
