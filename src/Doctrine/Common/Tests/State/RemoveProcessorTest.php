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

use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Doctrine\Common\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class RemoveProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $this->assertInstanceOf(ProcessorInterface::class, new RemoveProcessor($this->prophesize(ManagerRegistry::class)->reveal()));
    }

    public function testRemove(): void
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->remove($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        (new RemoveProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Delete(), []);
    }

    public function testRemoveWithNullManager(): void
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        (new RemoveProcessor($managerRegistryProphecy->reveal()))->process(new Dummy(), new Delete(), []);
    }
}
