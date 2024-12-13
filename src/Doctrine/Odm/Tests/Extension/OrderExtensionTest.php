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

namespace ApiPlatform\Doctrine\Odm\Tests\Extension;

use ApiPlatform\Doctrine\Odm\Extension\OrderExtension;
use ApiPlatform\Doctrine\Odm\Tests\Fixtures\Document\Dummy;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Lookup;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Unwind;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class OrderExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testApplyToCollectionWithValidOrder(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->getStage(0)->willThrow(new \OutOfRangeException('message'));
        $aggregationBuilderProphecy->sort(['name' => 'asc'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }

    public function testApplyToCollectionWithWrongOrder(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->getStage(0)->willThrow(new \OutOfRangeException('message'));
        $aggregationBuilderProphecy->sort(['name' => 'asc'])->shouldNotBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension(null, $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }

    public function testApplyToCollectionWithOrderOverridden(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->getStage(0)->willThrow(new \OutOfRangeException('message'));
        $aggregationBuilderProphecy->sort(['foo' => 'DESC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, (new GetCollection())->withOrder(['foo' => 'DESC']));
    }

    public function testApplyToCollectionWithOrderOverriddenWithNoDirection(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->getStage(0)->willThrow(new \OutOfRangeException('message'));
        $aggregationBuilderProphecy->sort(['foo' => 'ASC'])->shouldBeCalled();
        $aggregationBuilderProphecy->sort(['foo' => 'ASC', 'bar' => 'DESC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, (new GetCollection())->withOrder(['foo', 'bar' => 'DESC']));
    }

    public function testApplyToCollectionWithOrderOverriddenWithAssociation(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $lookupProphecy = $this->prophesize(Lookup::class);
        $lookupProphecy->localField('author')->shouldBeCalled()->willReturn($lookupProphecy);
        $lookupProphecy->foreignField('_id')->shouldBeCalled()->willReturn($lookupProphecy);
        $lookupProphecy->alias('author_lkup')->shouldBeCalled()->willReturn($lookupProphecy);
        $aggregationBuilderProphecy->lookup(Dummy::class)->shouldBeCalled()->willReturn($lookupProphecy->reveal());
        $unwindProphecy = $this->prophesize(Unwind::class);
        $unwindProphecy->preserveNullAndEmptyArrays(true)->shouldBeCalled()->willReturn($unwindProphecy->reveal());
        $aggregationBuilderProphecy->unwind('$author_lkup')->shouldBeCalled()->willReturn($unwindProphecy->reveal());
        $aggregationBuilderProphecy->getStage(0)->willThrow(new \OutOfRangeException('message'));
        $aggregationBuilderProphecy->sort(['author_lkup.name' => 'ASC'])->shouldBeCalled();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->shouldBeCalled()->willReturn(['name']);
        $classMetadataProphecy->hasAssociation('author')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->hasAssociation('name')->shouldBeCalled()->willReturn(false);
        $classMetadataProphecy->getAssociationTargetClass('author')->shouldBeCalled()->willReturn(Dummy::class);
        $classMetadataProphecy->hasReference('author')->shouldBeCalled()->willReturn(true);
        $classMetadataProphecy->getFieldMapping('author')->shouldBeCalled()->willReturn(['isOwningSide' => true, 'storeAs' => ClassMetadata::REFERENCE_STORE_AS_ID]);

        $objectManagerProphecy = $this->prophesize(DocumentManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->shouldBeCalled()->willReturn($classMetadataProphecy->reveal());

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldBeCalled()->willReturn($objectManagerProphecy->reveal());

        $aggregationBuilder = $aggregationBuilderProphecy->reveal();
        $orderExtensionTest = new OrderExtension('asc', $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class, (new GetCollection())->withOrder(['author.name']));
    }

    public function testApplyToCollectionWithExistingSortStage(): void
    {
        $aggregationBuilderProphecy = $this->prophesize(Builder::class);

        $aggregationBuilderProphecy->sort(['name' => 'asc'])->shouldNotBeCalled();
        $aggregationBuilderProphecy->getStage(0)->shouldBeCalled()->willReturn(new Sort($aggregationBuilder = $aggregationBuilderProphecy->reveal(), 'field'));

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->shouldNotBeCalled();

        $orderExtensionTest = new OrderExtension('asc', $managerRegistryProphecy->reveal());
        $orderExtensionTest->applyToCollection($aggregationBuilder, Dummy::class);
    }
}
