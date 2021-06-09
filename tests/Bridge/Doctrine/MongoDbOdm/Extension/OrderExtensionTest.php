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
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Lookup;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @group mongodb
 */
class OrderExtensionTest extends TestCase
{
    use ProphecyTrait;

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
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['foo' => 'DESC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => (new GetCollection())->withOrder(['foo' => 'DESC'])]))]);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyMetadata);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, 'get');
    }

    public function testApplyToCollectionWithOrderOverriddenWithNoDirection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['foo' => 'ASC'])->shouldBeCalled();
        $aggregationBuilderProphecy->sort(['foo' => 'ASC', 'bar' => 'DESC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => (new GetCollection())->withOrder(['foo', 'bar' => 'DESC'])]))]);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyMetadata);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, 'get');
    }

    public function testApplyToCollectionWithOrderOverriddenWithAssociation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
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

        $dummyMetadata = new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations(['get' => (new GetCollection())->withOrder(['author.name'])]))]);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn($dummyMetadata);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $resourceMetadataFactoryProphecy->reveal(), $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, 'get');
    }
}
