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

    public function testProcessWithNoCustomInputAndNoCustomOutput(): void
    {
        $this->skipIfMapParameterNotAvailable();

        $entity = new DummyEntity();
        $persisted = new DummyEntity();
        $operation = new Post(class: DummyEntity::class, map: true, write: true);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->never())->method('map');

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($entity, $operation, [], [])
            ->willReturn($persisted);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $result = $processor->process($entity, $operation);

        $this->assertSame($persisted, $result);
    }

    public function testProcessWithNoCustomInputAndCustomOutput(): void
    {
        $this->skipIfMapParameterNotAvailable();

        $entity = new DummyEntity();
        $persisted = new DummyEntity();
        $output = new DummyOutput();
        $operation = new Post(
            class: DummyEntity::class,
            output: ['class' => DummyOutput::class],
            map: true,
            write: true
        );

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($persisted, DummyOutput::class)
            ->willReturn($output);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($entity, $operation, [], [])
            ->willReturn($persisted);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $result = $processor->process($entity, $operation);

        $this->assertSame($output, $result);
    }

    public function testProcessWithCustomInputAndNoCustomOutput(): void
    {
        $this->skipIfMapParameterNotAvailable();

        $input = new DummyInput();
        $entity = new DummyEntity();
        $persisted = new DummyEntity();
        $operation = new Post(
            class: DummyEntity::class,
            input: ['class' => DummyInput::class],
            map: true,
            write: true
        );

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($input, null)
            ->willReturn($entity);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($entity, $operation, [], [])
            ->willReturn($persisted);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $result = $processor->process($input, $operation);

        $this->assertSame($persisted, $result);
    }

    public function testProcessWithCustomInputAndCustomOutput(): void
    {
        $this->skipIfMapParameterNotAvailable();

        $input = new DummyInput();
        $entity = new DummyEntity();
        $persisted = new DummyEntity();
        $output = new DummyOutput();
        $operation = new Post(
            class: DummyEntity::class,
            input: ['class' => DummyInput::class],
            output: ['class' => DummyOutput::class],
            map: true,
            write: true
        );

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->exactly(2))
            ->method('map')
            ->willReturnCallback(function ($data, $target) use ($input, $entity, $persisted, $output) {
                if ($data === $input && null === $target) {
                    return $entity;
                }
                if ($data === $persisted && DummyOutput::class === $target) {
                    return $output;
                }
                throw new \Exception('Unexpected map call');
            });

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($entity, $operation, [], [])
            ->willReturn($persisted);

        $processor = new ObjectMapperProcessor($objectMapper, $decorated);
        $result = $processor->process($input, $operation);

        $this->assertSame($output, $result);
    }

    private function skipIfMapParameterNotAvailable(): void
    {
        try {
            $reflection = new \ReflectionClass(Post::class);
            $constructor = $reflection->getConstructor();
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                if ('map' === $parameter->getName()) {
                    return;
                }
            }
            $this->markTestSkipped('The "map" parameter is not available in this version');
        } catch (\ReflectionException $e) {
            $this->markTestSkipped('Could not check for "map" parameter availability');
        }
    }
}

class DummyResourceWithoutMap
{
}

#[Map]
class DummyResourceWithMap
{
}

#[Map]
class DummyEntity
{
}

#[Map]
class DummyInput
{
}

#[Map]
class DummyOutput
{
}
