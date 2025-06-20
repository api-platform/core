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

namespace ApiPlatform\Symfony\Tests\Doctrine\EventListener;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PurgeHttpCacheListener;
use ApiPlatform\Symfony\Tests\Fixtures\NotAResource;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\ContainNonResource;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\DummyNoGetOperation;
use ApiPlatform\Symfony\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PurgeHttpCacheListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testOnFlush(): void
    {
        $toInsert1 = new Dummy();
        $toInsert2 = new Dummy();

        $toUpdate1 = new Dummy();
        $toUpdate1->setId(1);
        $toUpdate2 = new Dummy();
        $toUpdate2->setId(2);

        $toDelete1 = new Dummy();
        $toDelete1->setId(3);
        $toDelete2 = new Dummy();
        $toDelete2->setId(4);

        $toDeleteNoPurge = new DummyNoGetOperation();
        $toDeleteNoPurge->setId(5);

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies', '/dummies/1', '/dummies/2', '/dummies/3', '/dummies/4'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate1)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate2)->willReturn('/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete1)->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete2)->willReturn('/dummies/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(Argument::type(DummyNoGetOperation::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->willThrow(new InvalidArgumentException())->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(Argument::any())->willThrow(new ItemNotFoundException());

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert1, $toInsert2])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([$toUpdate1, $toUpdate2])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([$toDelete1, $toDelete2, $toDeleteNoPurge])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $dummyClassMetadata = new ClassMetadata(Dummy::class);
        // @phpstan-ignore-next-line
        $dummyClassMetadata->associationMappings = [
            'relatedDummy' => [],
            'relatedOwningDummy' => [],
        ];
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($dummyClassMetadata)->shouldBeCalled();
        $emProphecy->getClassMetadata(DummyNoGetOperation::class)->willReturn(new ClassMetadata(DummyNoGetOperation::class))->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->isReadable(Argument::type(Dummy::class), 'relatedDummy')->willReturn(true);
        $propertyAccessorProphecy->isReadable(Argument::type(Dummy::class), 'relatedOwningDummy')->willReturn(false);
        $propertyAccessorProphecy->getValue(Argument::type(Dummy::class), 'relatedDummy')->willReturn(null)->shouldBeCalled();
        $propertyAccessorProphecy->getValue(Argument::type(Dummy::class), 'relatedOwningDummy')->willReturn(null)->shouldNotBeCalled();

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal());
        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->shouldHaveBeenCalled();
        $iriConverterProphecy->getIriFromResource(Argument::type(DummyNoGetOperation::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->shouldHaveBeenCalled();
    }

    public function testPreUpdate(): void
    {
        $oldRelatedDummy = new RelatedDummy();
        $oldRelatedDummy->setId(1);

        $newRelatedDummy = new RelatedDummy();
        $newRelatedDummy->setId(2);

        $dummy = new Dummy();
        $dummy->setId(1);

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies', '/dummies/1', '/related_dummies/old', '/related_dummies/new'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummy)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($oldRelatedDummy)->willReturn('/related_dummies/old')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($newRelatedDummy)->willReturn('/related_dummies/new')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->mapManyToOne(['fieldName' => 'relatedDummy', 'targetEntity' => RelatedDummy::class]);
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadata)->shouldBeCalled();

        $changeSet = ['relatedDummy' => [$oldRelatedDummy, $newRelatedDummy]];
        $eventArgs = new PreUpdateEventArgs($dummy, $emProphecy->reveal(), $changeSet);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $listener->preUpdate($eventArgs);
        $listener->postFlush();
    }

    public function testNothingToPurge(): void
    {
        $dummyNoGetOperation = new DummyNoGetOperation();
        $dummyNoGetOperation->setId(1);

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge([])->shouldNotBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Argument::type(DummyNoGetOperation::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->willThrow(new InvalidArgumentException())->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummyNoGetOperation)->shouldNotBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyNoGetOperation::class))->willReturn(DummyNoGetOperation::class)->shouldNotBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(DummyNoGetOperation::class);
        $emProphecy->getClassMetadata(DummyNoGetOperation::class)->willReturn($classMetadata)->shouldBeCalled();

        $changeSet = ['lorem' => ['ipsum1', 'ipsum2']];
        $eventArgs = new PreUpdateEventArgs($dummyNoGetOperation, $emProphecy->reveal(), $changeSet);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $listener->preUpdate($eventArgs);
        $listener->postFlush();
    }

    public function testNotAResourceClass(): void
    {
        $containNonResource = new ContainNonResource();
        $nonResource1 = new NotAResource('foo', 'bar');
        $nonResource2 = new NotAResource('baz', 'qux');
        $collectionOfNotAResource = [$nonResource1, $nonResource2];

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Argument::type(ContainNonResource::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($nonResource1)->willThrow(new InvalidArgumentException())->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($nonResource2)->willThrow(new InvalidArgumentException())->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$containNonResource])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();

        $dummyClassMetadata = new ClassMetadata(ContainNonResource::class);
        // @phpstan-ignore-next-line
        $dummyClassMetadata->associationMappings = [
            'notAResource' => [],
            'collectionOfNotAResource' => ['targetEntity' => NotAResource::class],
        ];
        $emProphecy->getClassMetadata(ContainNonResource::class)->willReturn($dummyClassMetadata);
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->isReadable(Argument::type(ContainNonResource::class), 'notAResource')->willReturn(true);
        $propertyAccessorProphecy->isReadable(Argument::type(ContainNonResource::class), 'collectionOfNotAResource')->willReturn(true);
        $propertyAccessorProphecy->getValue(Argument::type(ContainNonResource::class), 'notAResource')->shouldBeCalled()->willReturn($nonResource1);
        $propertyAccessorProphecy->getValue(Argument::type(ContainNonResource::class), 'collectionOfNotAResource')->shouldBeCalled()->willReturn($collectionOfNotAResource);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal());
        $listener->onFlush($eventArgs);
        $listener->postFlush();
    }

    public function testAddTagsForCollection(): void
    {
        $dummy1 = new Dummy();
        $dummy1->setId(1);
        $dummy2 = new Dummy();
        $dummy2->setId(2);
        $collection = [$dummy1, $dummy2];

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies', '/dummies/1', '/dummies/2'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, new GetCollection())->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummy1)->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummy2)->willReturn('/dummies/2')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();

        $dummyWithCollection = new Dummy();
        $dummyWithCollection->setId(3);

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$dummyWithCollection])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();

        $dummyClassMetadata = new ClassMetadata(Dummy::class);
        // @phpstan-ignore-next-line
        $dummyClassMetadata->associationMappings = [
            'relatedDummies' => ['targetEntity' => Dummy::class],
        ];
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($dummyClassMetadata)->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->isReadable(Argument::type(Dummy::class), 'relatedDummies')->willReturn(true);
        $propertyAccessorProphecy->getValue(Argument::type(Dummy::class), 'relatedDummies')->willReturn($collection)->shouldBeCalled();

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal());
        $listener->onFlush($eventArgs);
        $listener->postFlush();
    }
}
