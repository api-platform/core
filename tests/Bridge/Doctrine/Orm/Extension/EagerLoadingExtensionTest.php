<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\EagerLoadingExtension;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
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

        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => RelatedDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'a11')->shouldBeCalled(1);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled(2)->willReturn($emProphecy->reveal());

        $eagerExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToItem()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy3', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy4', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy5', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'relation', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relatedDummy' => ['fetch' => 3, 'joinColumns' => [['nullable' => true]], 'targetEntity' => RelatedDummy::class],
            'relatedDummy2' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy3' => ['fetch' => 3, 'joinTable' => ['joinColumns' => [['nullable' => false]]], 'targetEntity' => UnknownDummy::class],
            'relatedDummy4' => ['fetch' => 3, 'targetEntity' => UnknownDummy::class],
            'relatedDummy5' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class],
        ];

        $relatedClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $relatedClassMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('a0.relation', 'a10')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'a111')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy3', 'a1122')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('o.relatedDummy4', 'a11233')->shouldBeCalled(1);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled(2)->willReturn($emProphecy->reveal());

        $orderExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }

    public function testCreateItemWithOperationName()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['item_operation_name' => 'item_operation'])->shouldBeCalled()->willReturn(new PropertyMetadata());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy);

        $orderExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, [], 'item_operation');
    }

    public function testCreateCollectionWithOperationName()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'foo', ['collection_operation_name' => 'collection_operation'])->shouldBeCalled()->willReturn(new PropertyMetadata());

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'foo' => ['fetch' => 1],
        ];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, 'collection_operation');
    }

    public function testDenormalizeItemWithCorrectResourceClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        //Dummy is the correct class for the denormalization context serialization groups, and we're fetching RelatedDummy
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), RelatedDummy::class, ['id' => 1], 'item_operation', ['resource_class' => Dummy::class]);
    }

    public function testDenormalizeItemWithExistingGroups()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        //groups exist from the context, we don't need to compute them again
        $resourceMetadataFactoryProphecy->create(RelatedDummy::class)->willReturn(new ResourceMetadata())->shouldBeCalled();
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled()->willReturn($emProphecy);

        $eagerExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
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

        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $relatedPropertyMetadata = new PropertyMetadata();
        $relatedPropertyMetadata = $relatedPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();
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
        $queryBuilderProphecy->getEntityManager()->willReturn($emProphecy->reveal());
        $queryBuilderProphecy->innerJoin(Argument::type('string'), Argument::type('string'))->shouldBeCalled();

        $eagerExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, false);
        $eagerExtensionTest->applyToCollection($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class);
    }

    public function testForceEager()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata());

        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relation', [])->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 2, 'targetEntity' => UnknownDummy::class, 'joinColumns' => [['nullable' => false]]],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->associationMappings = [];

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->innerJoin('o.relation', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->getEntityManager()->shouldBeCalled(2)->willReturn($emProphecy->reveal());

        $orderExtensionTest = new EagerLoadingExtension($propertyMetadataFactoryProphecy->reveal(), $resourceMetadataFactoryProphecy->reveal(), 30, true);
        $orderExtensionTest->applyToItem($queryBuilderProphecy->reveal(), new QueryNameGenerator(), Dummy::class, []);
    }
}
