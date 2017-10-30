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
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

/**
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class ChainDataPersisterTest extends TestCase
{
    public function testContruct()
    {
        $this->assertInstanceOf(DataPersisterInterface::class, new ChainDataPersister([$this->prophesize(DataPersisterInterface::class)->reveal()]));
    }

    public function testSupports()
    {
        $dummy = new Dummy();

        $persisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $persisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();

        $this->assertTrue((new ChainDataPersister([$persisterProphecy->reveal()]))->supports($dummy));
    }

    public function testDoesNotSupport()
    {
        $dummy = new Dummy();

        $persisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $persisterProphecy->supports($dummy)->willReturn(false)->shouldBeCalled();

        $this->assertFalse((new ChainDataPersister([$persisterProphecy->reveal()]))->supports($dummy));
    }

    public function testPersist()
    {
        $dummy = new Dummy();

        $fooPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $fooPersisterProphecy->supports($dummy)->willReturn(false)->shouldBeCalled();
        $fooPersisterProphecy->persist($dummy)->shouldNotBeCalled();

        $barPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $barPersisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();
        $barPersisterProphecy->persist($dummy)->shouldBeCalled();

        (new ChainDataPersister([$fooPersisterProphecy->reveal(), $barPersisterProphecy->reveal()]))->persist($dummy);
    }

    public function testRemove()
    {
        $dummy = new Dummy();

        $fooPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $fooPersisterProphecy->supports($dummy)->willReturn(false)->shouldBeCalled();
        $fooPersisterProphecy->remove($dummy)->shouldNotBeCalled();

        $barPersisterProphecy = $this->prophesize(DataPersisterInterface::class);
        $barPersisterProphecy->supports($dummy)->willReturn(true)->shouldBeCalled();
        $barPersisterProphecy->remove($dummy)->shouldBeCalled();

        (new ChainDataPersister([$fooPersisterProphecy->reveal(), $barPersisterProphecy->reveal()]))->remove($dummy);
    }
}
