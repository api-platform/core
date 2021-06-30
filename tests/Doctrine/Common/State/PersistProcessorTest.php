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

namespace ApiPlatform\Tests\Doctrine\Common\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prediction\CallPrediction;
use Prophecy\Prediction\NoCallsPrediction;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PersistProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $this->assertInstanceOf(ProcessorInterface::class, new PersistProcessor($this->prophesize(ManagerRegistry::class)->reveal(), PropertyAccess::createPropertyAccessor()));
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

        $result = (new PersistProcessor($managerRegistryProphecy->reveal(), PropertyAccess::createPropertyAccessor()))->process($dummy, new Get());
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
        $objectManagerProphecy->getClassMetadata(Dummy::class)->willReturn(null)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal(), PropertyAccess::createPropertyAccessor()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }

    public function testPersistWithNullManager(): void
    {
        $dummy = new Dummy();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal(), PropertyAccess::createPropertyAccessor()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }

    public function getTrackingPolicyParameters(): array
    {
        return [
            'deferred explicit ORM' => [ClassMetadataInfo::class, true, true],
            'deferred implicit ORM' => [ClassMetadataInfo::class, false, false],
            'deferred explicit ODM' => [ClassMetadata::class, true, true],
            'deferred implicit ODM' => [ClassMetadata::class, false, false],
        ];
    }

    /**
     * @dataProvider getTrackingPolicyParameters
     */
    public function testTrackingPolicy(string $metadataClass, bool $deferredExplicit, bool $persisted): void
    {
        $dummy = new Dummy();

        $classMetadataInfo = $this->prophesize($metadataClass);
        if (method_exists($metadataClass, 'isChangeTrackingDeferredExplicit')) {
            $classMetadataInfo->isChangeTrackingDeferredExplicit()->willReturn($deferredExplicit)->shouldBeCalled();
        } else {
            $persisted = false;
        }

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadataInfo)->shouldBeCalled();
        $objectManagerProphecy->contains($dummy)->willReturn(true);
        $objectManagerProphecy->persist($dummy)->should($persisted ? new CallPrediction() : new NoCallsPrediction());
        $objectManagerProphecy->flush()->shouldBeCalled();
        $objectManagerProphecy->refresh($dummy)->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy)->shouldBeCalled();

        $result = (new PersistProcessor($managerRegistryProphecy->reveal(), PropertyAccess::createPropertyAccessor()))->process($dummy, new Get());
        $this->assertSame($dummy, $result);
    }
}
