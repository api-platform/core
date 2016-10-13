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
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\UnknownDummy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
class EagerLoadingExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testApplyToCollection()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2')->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new PropertyMetadata();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new PropertyMetadata();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id')->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name')->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase')->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable')->willReturn($notReadablePropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn([0 => 'relatedDummy', 'relatedDummy2']);
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

        $relatedClassMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn([]);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'a11')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a0.{id,name}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a11.{id,name}')->shouldBeCalled(1);

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled(2)->willReturn($emProphecy->reveal());

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class);
    }

    public function testApplyToItem()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $relatedNameCollection = new PropertyNameCollection(['id', 'name', 'notindatabase', 'notreadable', 'relation']);

        $propertyNameCollectionFactoryProphecy->create(RelatedDummy::class)->willReturn($relatedNameCollection)->shouldBeCalled();
        $propertyNameCollectionFactoryProphecy->create(UnknownDummy::class)->willReturn(new PropertyNameCollection(['id']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $relationPropertyMetadata = new PropertyMetadata();
        $relationPropertyMetadata = $relationPropertyMetadata->withReadableLink(true);

        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy')->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy2')->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy3')->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'relatedDummy4')->willReturn($relationPropertyMetadata)->shouldBeCalled();

        $idPropertyMetadata = new PropertyMetadata();
        $idPropertyMetadata = $idPropertyMetadata->withIdentifier(true);
        $namePropertyMetadata = new PropertyMetadata();
        $namePropertyMetadata = $namePropertyMetadata->withReadable(true);
        $notInDatabasePropertyMetadata = new PropertyMetadata();
        $notInDatabasePropertyMetadata = $notInDatabasePropertyMetadata->withReadable(true);
        $notReadablePropertyMetadata = new PropertyMetadata();
        $notReadablePropertyMetadata = $notReadablePropertyMetadata->withReadable(false);

        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'id')->willReturn($idPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'name')->willReturn($namePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notindatabase')->willReturn($notInDatabasePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'notreadable')->willReturn($notReadablePropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(RelatedDummy::class, 'relation')->willReturn($relationPropertyMetadata)->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(UnknownDummy::class, 'id')->willReturn($idPropertyMetadata)->shouldBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn(['relatedDummy', 'relatedDummy2', 'relatedDummy3', 'relatedDummy4']);
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

        $relatedClassMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn(['relation']);

        $relatedClassMetadataProphecy->associationMappings = [
            'relation' => ['fetch' => 3, 'joinColumns' => [['nullable' => false]], 'targetEntity' => UnknownDummy::class],
        ];

        $unknownClassMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $unknownClassMetadataProphecy->getAssociationNames()->shouldBeCalled()->willReturn([]);

        $emProphecy = $this->prophesize(EntityManager::class);
        $emProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(RelatedDummy::class)->shouldBeCalled()->willReturn($relatedClassMetadataProphecy->reveal());
        $emProphecy->getClassMetadata(UnknownDummy::class)->shouldBeCalled()->willReturn($unknownClassMetadataProphecy->reveal());

        $queryBuilderProphecy->leftJoin('o.relatedDummy', 'a0')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('a0.relation', 'a10')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy2', 'a111')->shouldBeCalled(1);
        $queryBuilderProphecy->innerJoin('o.relatedDummy3', 'a1122')->shouldBeCalled(1);
        $queryBuilderProphecy->leftJoin('o.relatedDummy4', 'a11233')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a0.{id,name}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a10.{id}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a111.{id}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a1122.{id}')->shouldBeCalled(1);
        $queryBuilderProphecy->addSelect('partial a11233.{id}')->shouldBeCalled(1);

        $queryBuilderProphecy->getEntityManager()->shouldBeCalled(2)->willReturn($emProphecy->reveal());

        $queryBuilder = $queryBuilderProphecy->reveal();
        $orderExtensionTest = new EagerLoadingExtension($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal());

        $orderExtensionTest->applyToItem($queryBuilder, new QueryNameGenerator(), Dummy::class, []);
    }
}
