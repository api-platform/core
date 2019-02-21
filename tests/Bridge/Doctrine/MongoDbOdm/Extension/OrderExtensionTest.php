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

namespace ApiPlatform\Core\Tests\Bridge\Doctrine\MongoDbOdm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\OrderExtension;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Lookup;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class OrderExtensionTest extends TestCase
{
    public function testApplyToCollectionWithValidOrder()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['name' => 'asc'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', null, $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }

    public function testApplyToCollectionWithWrongOrder()
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['name' => 'asc'])->shouldNotBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension(null, null, $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverridden()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['foo' => 'DESC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['foo' => 'DESC']]));

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverriddenWithNoDirection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['foo' => 'ASC'])->shouldBeCalled();
        $aggregationBuilderProphecy->sort(['foo' => 'ASC', 'bar' => 'DESC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['foo', 'bar' => 'DESC']]));

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverriddenWithAssociation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $lookupProphecy = $this->prophesize(Lookup::class);
        $lookupProphecy->localField('author')->shouldBeCalled()->willReturn($lookupProphecy);
        $lookupProphecy->foreignField('_id')->shouldBeCalled()->willReturn($lookupProphecy);
        $lookupProphecy->alias('author_lkup')->shouldBeCalled();
        $aggregationBuilderProphecy->lookup(Dummy::class)->shouldBeCalled()->willReturn($lookupProphecy->reveal());
        $aggregationBuilderProphecy->unwind('$author_lkup')->shouldBeCalled();
        $aggregationBuilderProphecy->sort(['author_lkup.name' => 'ASC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);
        $classMetadataProphecy->hasAssociation('author')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->hasAssociation('name')->shouldBeCalled()->willReturn(false);
        $classMetadataProphecy->getAssociationTargetClass('author')->shouldBeCalled()->willReturn(Dummy::class);
        $classMetadataProphecy->hasReference('author')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->getFieldMapping('author')->shouldBeCalled()->willReturn(['isOwningSide' => true, 'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID]);

        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['author.name']]));

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }
}
