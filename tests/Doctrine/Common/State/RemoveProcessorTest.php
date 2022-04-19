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

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

class RemoveProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        $this->assertInstanceOf(ProcessorInterface::class, new RemoveProcessor($this->prophesize(ManagerRegistry::class)->reveal()));
    }

    public function testRemove()
    {
        $dummy = new Dummy();

        $objectManagerProphecy = $this->prophesize(ObjectManager::class);
        $objectManagerProphecy->remove($dummy)->shouldBeCalled();
        $objectManagerProphecy->flush()->shouldBeCalled();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($objectManagerProphecy->reveal())->shouldBeCalled();

        (new RemoveProcessor($managerRegistryProphecy->reveal()))->process($dummy, new Delete(), []);
    }

    public function testRemoveWithNullManager()
    {
        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn(null)->shouldBeCalled();

        (new RemoveProcessor($managerRegistryProphecy->reveal()))->process(new Dummy(), new Delete(), []);
    }
}
