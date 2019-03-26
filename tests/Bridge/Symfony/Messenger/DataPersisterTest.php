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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Messenger;

use ApiPlatform\Core\Bridge\Symfony\Messenger\DataPersister;
use ApiPlatform\Core\Bridge\Symfony\Messenger\RemoveStamp;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\DummyCar;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataPersisterTest extends TestCase
{
    public function testSupport()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['messenger' => true]));

        $dataPersister = new DataPersister($metadataFactoryProphecy->reveal(), $this->prophesize(MessageBusInterface::class)->reveal());
        $this->assertTrue($dataPersister->supports(new Dummy()));
    }

    public function testSupportWithContext()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['messenger' => true]));
        $metadataFactoryProphecy->create(DummyCar::class)->willThrow(new ResourceClassNotFoundException());

        $dataPersister = new DataPersister($metadataFactoryProphecy->reveal(), $this->prophesize(MessageBusInterface::class)->reveal());
        $this->assertTrue($dataPersister->supports(new DummyCar(), ['resource_class' => Dummy::class]));
        $this->assertFalse($dataPersister->supports(new DummyCar()));
    }

    public function testPersist()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch($dummy)->willReturn(new Envelope($dummy))->shouldBeCalled();

        $dataPersister = new DataPersister($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(), $messageBus->reveal());
        $this->assertSame($dummy, $dataPersister->persist($dummy));
    }

    public function testRemove()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(RemoveStamp::class);
        }))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $dataPersister = new DataPersister($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(), $messageBus->reveal());
        $dataPersister->remove($dummy);
    }

    public function testHandle()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch($dummy)->willReturn(new Envelope($dummy, new HandledStamp($dummy, 'DummyHandler::__invoke')))->shouldBeCalled();

        $dataPersister = new DataPersister($this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(), $messageBus->reveal());
        $this->assertSame($dummy, $dataPersister->persist($dummy));
    }

    public function testSupportWithGraphqlContext()
    {
        $metadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $metadataFactoryProphecy->create(Dummy::class)->willReturn((new ResourceMetadata(null, null, null, null, null, []))->withGraphQl(['create' => ['messenger' => 'input']]));

        $dataPersister = new DataPersister($metadataFactoryProphecy->reveal(), $this->prophesize(MessageBusInterface::class)->reveal());
        $this->assertTrue($dataPersister->supports(new DummyCar(), ['resource_class' => Dummy::class, 'graphql_operation_name' => 'create']));
    }
}
