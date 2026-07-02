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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Symfony\Doctrine\EventListener\PurgeHttpCacheListener;
use ApiPlatform\Symfony\Tests\Fixtures\MappedEntity;
use ApiPlatform\Symfony\Tests\Fixtures\MappedResource;
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
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
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
        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate1, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toUpdate2, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/2')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete1, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/3')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($toDelete2, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/4')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(Argument::type(DummyNoGetOperation::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->willThrow(new InvalidArgumentException())->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource(Argument::any())->willThrow(new ItemNotFoundException());

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyNoGetOperation::class))->willReturn(DummyNoGetOperation::class)->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                    new Get(),
                ]),
            ]));
        $resourceMetadataCollectionFactoryProphecy
            ->create(DummyNoGetOperation::class)->willReturn(new ResourceMetadataCollection(DummyNoGetOperation::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                ]),
            ]));

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

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal(), null, null,
            $resourceMetadataCollectionFactoryProphecy->reveal(), );

        $listener->onFlush($eventArgs);
        $listener->postFlush();

        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->shouldHaveBeenCalled();
        $iriConverterProphecy->getIriFromResource(Argument::type(DummyNoGetOperation::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->shouldHaveBeenCalled();
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
        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummy, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($oldRelatedDummy, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/related_dummies/old')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($newRelatedDummy, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/related_dummies/new')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();

        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(RelatedDummy::class))->willReturn(RelatedDummy::class)->shouldBeCalled();

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                    new Get(),
                ]),
            ]));
        $resourceMetadataCollectionFactoryProphecy
            ->create(RelatedDummy::class)->willReturn(new ResourceMetadataCollection(RelatedDummy::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                    new Get(),
                ]),
            ]));

        $emProphecy = $this->prophesize(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(Dummy::class);
        $classMetadata->mapManyToOne(['fieldName' => 'relatedDummy', 'targetEntity' => RelatedDummy::class]);
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadata)->shouldBeCalled();

        $changeSet = ['relatedDummy' => [$oldRelatedDummy, $newRelatedDummy]];
        $eventArgs = new PreUpdateEventArgs($dummy, $emProphecy->reveal(), $changeSet);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(), null, null, null,
            $resourceMetadataCollectionFactoryProphecy->reveal());
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
        $resourceClassResolverProphecy->isResourceClass(DummyNoGetOperation::class)->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(DummyNoGetOperation::class))->willReturn(DummyNoGetOperation::class)->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);

        $classMetadata = new ClassMetadata(DummyNoGetOperation::class);
        $emProphecy->getClassMetadata(DummyNoGetOperation::class)->willReturn($classMetadata)->shouldBeCalled();

        $changeSet = ['lorem' => ['ipsum1', 'ipsum2']];
        $eventArgs = new PreUpdateEventArgs($dummyNoGetOperation, $emProphecy->reveal(), $changeSet);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(DummyNoGetOperation::class)->willReturn(new ResourceMetadataCollection(DummyNoGetOperation::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                ]),
            ]));

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(), $resourceClassResolverProphecy->reveal(),
            null, null, null, $resourceMetadataCollectionFactoryProphecy->reveal());
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
        $iriConverterProphecy->getIriFromResource(Argument::type(ContainNonResource::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->willReturn('/dummies')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(ContainNonResource::class))->willReturn(ContainNonResource::class)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(NotAResource::class))->willReturn(NotAResource::class);

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

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(ContainNonResource::class)->willReturn(new ResourceMetadataCollection(ContainNonResource::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                ]),
            ]));
        $resourceMetadataCollectionFactoryProphecy
            ->create(NotAResource::class)->willReturn(new ResourceMetadataCollection(NotAResource::class, []));

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal(),
            null, null, $resourceMetadataCollectionFactoryProphecy->reveal(), );
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
        $purgerProphecy->purge(['/dummies/1', '/dummies/2', '/dummies'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getIriFromResource(Argument::type(Dummy::class), UrlGeneratorInterface::ABS_PATH, Argument::type(GetCollection::class))->willReturn('/dummies')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummy1, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/1')->shouldBeCalled();
        $iriConverterProphecy->getIriFromResource($dummy2, UrlGeneratorInterface::ABS_PATH, Argument::type(Get::class))->willReturn('/dummies/2')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(true)->shouldBeCalled();
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class)->shouldBeCalled();

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

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                    new Get(),
                ]),
            ]));
        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal(), null, null,
            $resourceMetadataCollectionFactoryProphecy->reveal());
        $listener->onFlush($eventArgs);
        $listener->postFlush();
    }

    public function testOnFlushWithMultipleGetCollectionOperations(): void
    {
        $toInsert = new Dummy();

        $getCollection1 = new GetCollection(uriTemplate: '/dummies');
        $getCollection2 = new GetCollection(uriTemplate: '/dummies/special');

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/dummies', '/dummies/special'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy
            ->getIriFromResource(
                Argument::type(Dummy::class),
                UrlGeneratorInterface::ABS_PATH,
                Argument::that(static fn (GetCollection $operation): bool => '/dummies' === $operation->getUriTemplate())
            )
            ->willReturn('/dummies')
            ->shouldBeCalled();
        $iriConverterProphecy
            ->getIriFromResource(
                Argument::type(Dummy::class),
                UrlGeneratorInterface::ABS_PATH,
                Argument::that(static fn (GetCollection $operation): bool => '/dummies/special' === $operation->getUriTemplate())
            )
            ->willReturn('/dummies/special')
            ->shouldBeCalled();
        $iriConverterProphecy
            ->getIriFromResource(
                Argument::type(Dummy::class),
                UrlGeneratorInterface::ABS_PATH,
                new GetCollection()
            )
            ->willReturn('/dummies');

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass(Argument::type(Dummy::class))->willReturn(Dummy::class);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [
                new ApiResource(operations: [
                    $getCollection1,
                    $getCollection2,
                ]),
            ]));

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$toInsert])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $dummyClassMetadata = new ClassMetadata(Dummy::class);
        $dummyClassMetadata->associationMappings = [];
        $emProphecy->getClassMetadata(Dummy::class)->willReturn($dummyClassMetadata)->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal(), null, null,
            $resourceMetadataCollectionFactoryProphecy->reveal(),
        );
        $listener->onFlush($eventArgs);
        $listener->postFlush();
    }

    public function testMappedResources(): void
    {
        $mappedEntity = new MappedEntity();
        $mappedEntity->setFirstName('first');
        $mappedEntity->setlastName('last');

        $mappedResource = new MappedResource();
        $mappedResource->username = $mappedEntity->getFirstName().' '.$mappedEntity->getLastName();

        $purgerProphecy = $this->prophesize(PurgerInterface::class);
        $purgerProphecy->purge(['/mapped_ressources'])->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        // the entity is not a resource, shouldn't be called
        $iriConverterProphecy->getIriFromResource(
            Argument::type(MappedEntity::class), UrlGeneratorInterface::ABS_PATH, new GetCollection()
        )->shouldNotBeCalled();
        // this should be called instead
        $iriConverterProphecy->getIriFromResource(
            Argument::type(MappedResource::class), UrlGeneratorInterface::ABS_PATH, new GetCollection()
        )->willReturn('/mapped_ressources')->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(MappedEntity::class)->willReturn(false)->shouldBeCalled();

        $resourceClassResolverProphecy->getResourceClass(Argument::type(MappedResource::class))->willReturn(MappedResource::class)->shouldBeCalled();

        $objectMapperProphecy = $this->prophesize(ObjectMapperInterface::class);
        $objectMapperProphecy->map($mappedEntity, MappedResource::class)->shouldBeCalled()->willReturn($mappedResource);

        $uowProphecy = $this->prophesize(UnitOfWork::class);
        $uowProphecy->getScheduledEntityInsertions()->willReturn([$mappedEntity])->shouldBeCalled();
        $uowProphecy->getScheduledEntityUpdates()->willReturn([])->shouldBeCalled();
        $uowProphecy->getScheduledEntityDeletions()->willReturn([])->shouldBeCalled();

        $emProphecy = $this->prophesize(EntityManagerInterface::class);
        $emProphecy->getUnitOfWork()->willReturn($uowProphecy->reveal())->shouldBeCalled();
        $classMetadata = new ClassMetadata(MappedEntity::class);
        $classMetadata->associationMappings = [];
        $emProphecy->getClassMetadata(MappedEntity::class)->willReturn($classMetadata)->shouldBeCalled();
        $eventArgs = new OnFlushEventArgs($emProphecy->reveal());

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy
            ->create(MappedResource::class)->willReturn(new ResourceMetadataCollection(MappedResource::class, [
                new ApiResource(operations: [
                    new GetCollection(),
                ]),
            ]));

        $listener = new PurgeHttpCacheListener($purgerProphecy->reveal(), $iriConverterProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(), $propertyAccessorProphecy->reveal(),
            $objectMapperProphecy->reveal(), null, $resourceMetadataCollectionFactoryProphecy->reveal()
        );
        $listener->onFlush($eventArgs);
        $listener->postFlush();
    }
}
