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

namespace ApiPlatform\Doctrine\Common\Tests\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Doctrine\Common\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Doctrine\Common\Tests\Fixtures\TestBundle\Entity\DummyWithUninitializedProperties;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork as ODMUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\ORM\UnitOfWork as ORMUnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prediction\CallPrediction;
use Prophecy\Prediction\NoCallsPrediction;

class PersistProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $this->assertInstanceOf(ProcessorInterface::class, new PersistProcessor($this->prophesize(ManagerRegistry::class)->reveal()));
    }

    public function testPersist(): void
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->contains($dummy)->willReturn(false);
        $objectManagerProphecy->persist($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }

    public function testPersistIfEntityAlreadyManaged(): void
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->contains($dummy)->willReturn(true);
        $objectManagerProphecy->persist($dummy)->shouldNotBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();
        $objectManagerProphecy->getClassMetadata(Dummy::class)->willReturn(new ClassMetadata(Dummy::class))->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }

    public function testPersistWithNullManager(): void
    {
        $dummy = new Dummy();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }

    public static function getTrackingPolicyParameters(): array
    {
        return [
            'deferred explicit ORM' => [ORMClassMetadata::class, true, true],
            'deferred implicit ORM' => [ORMClassMetadata::class, false, false],
            'deferred explicit ODM' => [ClassMetadata::class, true, true],
            'deferred implicit ODM' => [ClassMetadata::class, false, false],
        ];
    }

    #[DataProvider('getTrackingPolicyParameters')]
    public function testTrackingPolicy(string $metadataClass, bool $deferredExplicit, bool $persisted): void
    {
        $dummy = new Dummy();

        $classMetadata = $this->prophesize($metadataClass);
        if (method_exists($metadataClass, 'isChangeTrackingDeferredExplicit')) {
            $classMetadata->isChangeTrackingDeferredExplicit()->willReturn($deferredExplicit)->shouldBeCalled();
        } else {
            $persisted = false;
        }

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadata)->shouldBeCalled();
        $objectManagerProphecy->contains($dummy)->willReturn(true);
        $objectManagerProphecy->persist($dummy)->should($persisted ? new CallPrediction() : new NoCallsPrediction());
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy)->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }

    public function testHandleLazyObjectRelationsSkipsUninitializedProperties(): void
    {
        $dummy = new DummyWithUninitializedProperties();
        $dummy->title = 'My Book';

        $classMetadata = new ORMClassMetadata(DummyWithUninitializedProperties::class);
        $classMetadata->identifier = ['id'];

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->getClassMetadata(DummyWithUninitializedProperties::class)->willReturn($classMetadata);
        $objectManagerProphecy->contains($dummy)->willReturn(false);
        $objectManagerProphecy->persist($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(DummyWithUninitializedProperties::class)->willReturn($objectManagerProphecy->reveal());

        $result = (new PersistProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Post(map: true));
        $this->assertSame($dummy, $result);
    }

    public function testHandleLazyObjectRelationsKeepsNewOrmRelationWithAssignedIdentifier(): void
    {
        $relation = new PersistProcessorTestRelationStub();
        $data = new PersistProcessorTestObjectWithRelationStub($relation);

        $unitOfWork = $this->createMock(ORMUnitOfWork::class);
        $unitOfWork->expects($this->once())->method('getEntityState')->with($relation)->willReturn(ORMUnitOfWork::STATE_NEW);

        $relationManager = $this->createMock(EntityManagerInterface::class);
        $relationManager->method('contains')->with($relation)->willReturn(false);
        $relationManager->method('getClassMetadata')->with(PersistProcessorTestRelationStub::class)->willReturn($this->createOrmRelationMetadata());
        $relationManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $relationManager->expects($this->never())->method('getReference');

        $this->processObjectWithRelation($data, $relationManager);

        $this->assertSame($relation, $data->relation);
    }

    public function testHandleLazyObjectRelationsKeepsNewOdmRelationWithAssignedIdentifier(): void
    {
        $relation = new PersistProcessorTestRelationStub();
        $data = new PersistProcessorTestObjectWithRelationStub($relation);

        $unitOfWork = $this->createMock(PersistProcessorTestDocumentUnitOfWorkStub::class);
        $unitOfWork->expects($this->once())->method('getDocumentState')->with($relation)->willReturn(ODMUnitOfWork::STATE_NEW);

        $relationManager = $this->createMockForIntersectionOfInterfaces([ObjectManager::class, PersistProcessorTestDocumentManagerStub::class]);
        $relationManager->method('contains')->with($relation)->willReturn(false);
        $relationManager->method('getClassMetadata')->with(PersistProcessorTestRelationStub::class)->willReturn($this->createRelationMetadata());
        $relationManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $relationManager->expects($this->never())->method('getReference');

        $this->processObjectWithRelation($data, $relationManager);

        $this->assertSame($relation, $data->relation);
    }

    public function testHandleLazyObjectRelationsReplacesRelationWhenManagerHasNoUnitOfWork(): void
    {
        $relation = new PersistProcessorTestRelationStub();
        $reference = new PersistProcessorTestRelationStub();
        $data = new PersistProcessorTestObjectWithRelationStub($relation);

        $relationManager = $this->createMockForIntersectionOfInterfaces([ObjectManager::class, PersistProcessorTestReferenceManagerStub::class]);
        $relationManager->method('contains')->with($relation)->willReturn(false);
        $relationManager->method('getClassMetadata')->with(PersistProcessorTestRelationStub::class)->willReturn($this->createRelationMetadata());
        $relationManager->expects($this->once())->method('getReference')->with(PersistProcessorTestRelationStub::class, ['id' => 'relation-id'])->willReturn($reference);

        $this->processObjectWithRelation($data, $relationManager);

        $this->assertSame($reference, $data->relation);
    }

    public function testPersistPutCreateResolvesParentLinkViaToProperty(): void
    {
        $device = new PersistProcessorTestDeviceStub();

        $userReference = new PersistProcessorTestUserStub();
        $userReference->id = 'user-uuid';

        $deviceClassMetadata = new ORMClassMetadata(PersistProcessorTestDeviceStub::class);
        $deviceClassMetadata->identifier = ['id'];

        $deviceManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $deviceManagerProphecy->getClassMetadata(PersistProcessorTestDeviceStub::class)->willReturn($deviceClassMetadata);
        $deviceManagerProphecy->contains($device)->willReturn(false);
        $deviceManagerProphecy->persist($device)->shouldBeCalled();
        $deviceManagerProphecy->flush()->shouldBeCalled();
        $deviceManagerProphecy->refresh($device)->shouldBeCalled();

        $userManagerProphecy = $this->prophesize(EntityManagerInterface::class);
        $userManagerProphecy->getReference(PersistProcessorTestUserStub::class, 'user-uuid')
            ->willReturn($userReference)
            ->shouldBeCalledTimes(1);
        $userManagerProphecy->contains($userReference)->willReturn(true);

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(PersistProcessorTestDeviceStub::class)->willReturn($deviceManagerProphecy->reveal());
        $managerRegistryProphecy->getManagerForClass(PersistProcessorTestUserStub::class)->willReturn($userManagerProphecy->reveal());

        $operation = new Put(
            extraProperties: ['standard_put' => true],
            uriVariables: [
                'userId' => new Link(toProperty: 'user', fromClass: PersistProcessorTestUserStub::class, identifiers: ['id']),
                'id' => new Link(fromClass: PersistProcessorTestDeviceStub::class, identifiers: ['id']),
            ],
        );

        $result = (new PersistProcessor($managerRegistryProphecy->reveal()))->process(
            $device,
            $operation,
            ['userId' => 'user-uuid', 'id' => 'device-uuid'],
            ['previous_data' => null],
        );

        $this->assertSame($device, $result);
        $this->assertSame($userReference, $device->user);
        $this->assertSame('device-uuid', $device->id);
    }

    private function createRelationMetadata(): PersistenceClassMetadata
    {
        $metadata = $this->createStub(PersistenceClassMetadata::class);
        $metadata->method('getIdentifierValues')->willReturn(['id' => 'relation-id']);

        return $metadata;
    }

    private function createOrmRelationMetadata(): ORMClassMetadata
    {
        $metadata = new ORMClassMetadata(PersistProcessorTestRelationStub::class);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true]);
        $metadata->wakeupReflection(new RuntimeReflectionService());

        return $metadata;
    }

    private function processObjectWithRelation(PersistProcessorTestObjectWithRelationStub $data, ObjectManager $relationManager): void
    {
        $dataManager = $this->createMock(ObjectManager::class);
        $dataManager->method('getClassMetadata')->with(PersistProcessorTestObjectWithRelationStub::class)->willReturn($this->createStub(PersistenceClassMetadata::class));
        $dataManager->method('contains')->with($data)->willReturn(false);
        $dataManager->expects($this->once())->method('persist')->with($data);
        $dataManager->expects($this->once())->method('flush');
        $dataManager->expects($this->once())->method('refresh')->with($data);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturnMap([
            [PersistProcessorTestObjectWithRelationStub::class, $dataManager],
            [PersistProcessorTestRelationStub::class, $relationManager],
        ]);

        (new PersistProcessor($managerRegistry))->process($data, new Post(map: true));
    }
}

/** @internal */
class PersistProcessorTestUserStub
{
    public ?string $id = null;
}

/** @internal */
class PersistProcessorTestDeviceStub
{
    public ?string $id = null;
    public ?PersistProcessorTestUserStub $user = null;
}

/** @internal */
class PersistProcessorTestObjectWithRelationStub
{
    public ?string $id = null;

    public function __construct(public PersistProcessorTestRelationStub $relation)
    {
    }
}

/** @internal */
class PersistProcessorTestRelationStub
{
    public string $id = 'relation-id';
}

/** @internal */
interface PersistProcessorTestReferenceManagerStub
{
    /**
     * @param class-string         $className
     * @param array<string, mixed> $identifier
     */
    public function getReference(string $className, array $identifier): object;
}

/** @internal */
interface PersistProcessorTestDocumentManagerStub extends PersistProcessorTestReferenceManagerStub
{
    public function getUnitOfWork(): PersistProcessorTestDocumentUnitOfWorkStub;
}

/** @internal */
interface PersistProcessorTestDocumentUnitOfWorkStub
{
    public function getDocumentState(object $object): int;
}
