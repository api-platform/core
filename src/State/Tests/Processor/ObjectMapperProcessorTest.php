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

namespace ApiPlatform\State\Tests\Processor;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\Processor\ObjectMapperProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ObjectMapperProcessorTest extends TestCase
{
    public function testProcessBypassesWhenNoObjectMapper(): void
    {
        $data = new DummyResourceWithoutMap();
        $operation = new Post(class: DummyResourceWithoutMap::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor(null, $decorated);
        $this->assertEquals($data, $processor->process($data, $operation));
    }

    public function testProcessBypassesOnNonWriteOperation(): void
    {
        $data = new DummyResourceWithoutMap();
        $operation = new Get(class: DummyResourceWithoutMap::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertEquals($data, $processor->process($data, $operation));
    }

    public function testProcessBypassesWithNullData(): void
    {
        $operation = new Post(class: DummyResourceWithoutMap::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with(null, $operation, [], [])
            ->willReturn(null);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertNull($processor->process(null, $operation));
    }

    public function testProcessBypassesWithMismatchedDataType(): void
    {
        $data = new \stdClass();
        $operation = new Post(class: DummyResourceWithMap::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertEquals($data, $processor->process($data, $operation));
    }

    public function testProcessBypassesWithoutMapAttribute(): void
    {
        $data = new DummyResourceWithoutMap();
        $operation = new Post(class: DummyResourceWithoutMap::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertEquals($data, $processor->process($data, $operation));
    }
}

class DummyResourceWithoutMap
{
}

#[Map]
class DummyResourceWithMap
{
}
