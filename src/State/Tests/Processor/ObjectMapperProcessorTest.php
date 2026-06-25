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
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ObjectMapperProcessorTest extends TestCase
{
    public function testProcessBypassesWhenNoObjectMapper(): void
    {
        $data = new \stdClass();
        $operation = new Post(class: \stdClass::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor(null, $decorated);
        $this->assertSame($data, @$processor->process($data, $operation));
    }

    public function testProcessBypassesOnNonWriteOperation(): void
    {
        $data = new \stdClass();
        $operation = new Get(class: \stdClass::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertSame($data, @$processor->process($data, $operation));
    }

    /**
     * Regression test: input explicitly disabled (`input: false`) must not crash
     * `is_a($data, $class, true)` with a null $class — it should bypass to the decorated processor instead.
     */
    public function testProcessBypassesWhenInputDisabled(): void
    {
        $data = new \stdClass();
        $operation = new Post(class: \stdClass::class, input: false, map: true, write: true);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertSame($data, @$processor->process($data, $operation));
    }

    public function testProcessMapsInputToEntity(): void
    {
        $dto = new \stdClass();
        $entity = new \stdClass();
        $persisted = new \stdClass();
        $final = new \stdClass();
        $operation = new Post(class: \stdClass::class, map: true, write: true);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->exactly(2))
            ->method('map')
            ->willReturnOnConsecutiveCalls($entity, $final);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($entity, $operation, [], $this->anything())
            ->willReturn($persisted);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $this->assertSame($final, @$processor->process($dto, $operation));
    }
}