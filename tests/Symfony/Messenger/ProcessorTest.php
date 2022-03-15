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

namespace ApiPlatform\Tests\Symfony\Messenger;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\Messenger\ContextStamp;
use ApiPlatform\Symfony\Messenger\Processor;
use ApiPlatform\Symfony\Messenger\RemoveStamp;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class ProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testSupport()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withMessenger(true),
            'create' => (new Post())->withMessenger(true),
        ]))]));

        $processor = new Processor($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(MessageBusInterface::class)->reveal());
        $this->assertTrue($processor->supports(new Dummy(), [], 'get'));
        $this->assertTrue($processor->supports(new Dummy(), [], 'create'));
    }

    public function testSupportWithContext()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection(Dummy::class, [(new ApiResource())->withOperations(new Operations([
            'get' => (new Get())->withMessenger(true),
        ]))]));
        $resourceMetadataFactoryProphecy->create(DummyCar::class)->shouldBeCalled()->willThrow(new OperationNotFoundException());

        $processor = new Processor($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(MessageBusInterface::class)->reveal());
        $this->assertTrue($processor->supports(new DummyCar(), [], null, ['resource_class' => Dummy::class]));
        $this->assertFalse($processor->supports(new DummyCar()));
    }

    public function testPersist()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(ContextStamp::class);
        }))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $processor = new Processor($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $messageBus->reveal());
        $this->assertSame($dummy, $processor->process($dummy));
    }

    public function testRemove()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);

        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(RemoveStamp::class);
        }))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $processor = new Processor($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $messageBus->reveal());
        $processor->process($dummy, [], null, ['operation' => new Delete()]);
    }

    public function testHandle()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(ContextStamp::class);
        }))->willReturn((new Envelope($dummy))->with(new HandledStamp($dummy, 'DummyHandler::__invoke')))->shouldBeCalled();

        $processor = new Processor($this->prophesize(ResourceMetadataCollectionFactoryInterface::class)->reveal(), $messageBus->reveal());
        $this->assertSame($dummy, $processor->process($dummy));
    }

    public function testSupportWithGraphqlContext()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->willReturn(new ResourceMetadataCollection('Dummy', [(new ApiResource())->withGraphQlOperations(['create' => (new Mutation())->withMessenger(true)])]));

        $processor = new Processor($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(MessageBusInterface::class)->reveal());
        $this->assertTrue($processor->supports(new DummyCar(), [], 'create', ['resource_class' => Dummy::class, 'graphql_operation_name' => 'create']));
    }
}
