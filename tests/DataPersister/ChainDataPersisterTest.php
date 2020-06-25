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

namespace ApiPlatform\Core\Tests\DataPersister;

use ApiPlatform\Core\DataPersister\ChainDataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use ApiPlatform\Core\DataPersister\LoopDataPersisterInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ChainDataPersisterTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct()
    {
        $this->assertInstanceOf(DataPersisterInterface::class, new ChainDataPersister([$this->prophesize(DataPersisterInterface::class)->reveal()]));
    }

    public function testSupports()
    {
        $dummy = new Dummy();

        $persisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $persisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();

        $this->assertTrue((new ChainDataPersister([$persisterProphecy->reveal()]))->supports($dummy));
    }

    public function testDoesNotSupport()
    {
        $dummy = new Dummy();

        $persisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $persisterProphecy->supports($dummy, Argument::type('array'))->willReturn(false)->shouldBeCalled();

        $this->assertFalse((new ChainDataPersister([$persisterProphecy->reveal()]))->supports($dummy));
    }

    public function testPersist()
    {
        $dummy = new Dummy();

        $fooPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $fooPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(false)->shouldBeCalled();
        $fooPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();

        $loopPersisterProphecy = $this->prophesize(DataPersisterInterface::class)->willImplement(LoopDataPersisterInterface::class);
        $loopPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $loopPersisterProphecy->loop($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $loopPersisterProphecy->persist($dummy, Argument::type('array'))->shouldBeCalled();

        $barPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $barPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $barPersisterProphecy->persist($dummy, Argument::type('array'))->shouldBeCalled();

        $foobarPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $foobarPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $foobarPersisterProphecy->persist($dummy, Argument::type('array'))->shouldNotBeCalled();

        (new ChainDataPersister([$fooPersisterProphecy->reveal(), $loopPersisterProphecy->reveal(), $barPersisterProphecy->reveal(), $foobarPersisterProphecy->reveal()]))->persist($dummy);
    }

    public function testRemove()
    {
        $dummy = new Dummy();

        $fooPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $fooPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(false)->shouldBeCalled();
        $fooPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        $loopPersisterProphecy = $this->prophesize(DataPersisterInterface::class)->willImplement(LoopDataPersisterInterface::class);
        $loopPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $loopPersisterProphecy->loop($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $loopPersisterProphecy->remove($dummy, Argument::type('array'))->shouldBeCalled();

        $barPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $barPersisterProphecy->supports($dummy, Argument::type('array'))->willReturn(true)->shouldBeCalled();
        $barPersisterProphecy->remove($dummy, Argument::type('array'))->shouldBeCalled();

        $foobarPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $foobarPersisterProphecy->supports($dummy, Argument::type('array'))->shouldNotBeCalled();
        $foobarPersisterProphecy->remove($dummy, Argument::type('array'))->shouldNotBeCalled();

        (new ChainDataPersister([$fooPersisterProphecy->reveal(), $loopPersisterProphecy->reveal(), $barPersisterProphecy->reveal(), $foobarPersisterProphecy->reveal()]))->remove($dummy);
    }
}
