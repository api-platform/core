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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class ProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testPersist(): void
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(fn (Envelope $envelope) => $dummy === $envelope->getMessage() && null !== $envelope->last(ContextStamp::class)))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $processor = new Processor($messageBus->reveal());
        $this->assertSame($dummy, $processor->process($dummy, new Get()));
    }

    public function testRemove(): void
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);

        $messageBus->dispatch(Argument::that(fn (Envelope $envelope) => $dummy === $envelope->getMessage() && null !== $envelope->last(RemoveStamp::class)))->willReturn(new Envelope($dummy))->shouldBeCalled();

        $processor = new Processor($messageBus->reveal());
        $processor->process($dummy, new Delete());
    }

    public function testHandle(): void
    {
        $dummy = new Dummy();

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(fn (Envelope $envelope) => $dummy === $envelope->getMessage() && null !== $envelope->last(ContextStamp::class)))->willReturn((new Envelope($dummy))->with(new HandledStamp($dummy, 'DummyHandler::__invoke')))->shouldBeCalled();

        $processor = new Processor($messageBus->reveal());
        $this->assertSame($dummy, $processor->process($dummy, new Get()));
    }
}
