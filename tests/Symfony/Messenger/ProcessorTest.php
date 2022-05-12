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

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Symfony\Messenger\ContextStamp;
use ApiPlatform\Symfony\Messenger\Processor;
use ApiPlatform\Symfony\Messenger\RemoveStamp;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class ProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testPersist()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(ContextStamp::class);
        }))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $processor = new Processor($messageBus->reveal());
        $this->assertSame($dummy, $processor->process($dummy, new Get()));
    }

    public function testRemove()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);

        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(RemoveStamp::class);
        }))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $processor = new Processor($messageBus->reveal());
        $processor->process($dummy, new Delete());
    }

    public function testHandle()
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(function (Envelope $envelope) use ($dummy) {
            return $dummy === $envelope->getMessage() && null !== $envelope->last(ContextStamp::class);
        }))->willReturn((new Envelope($dummy))->with(new HandledStamp($dummy, 'DummyHandler::__invoke')))->shouldBeCalled();

        $processor = new Processor($messageBus->reveal());
        $this->assertSame($dummy, $processor->process($dummy, new Get()));
    }
}
