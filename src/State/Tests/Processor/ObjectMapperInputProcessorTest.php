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
use ApiPlatform\State\Processor\ObjectMapperInputProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ObjectMapperInputProcessorTest extends TestCase
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

        $processor = new ObjectMapperInputProcessor(null, $decorated);
        $this->assertSame($data, $processor->process($data, $operation));
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

        $processor = new ObjectMapperInputProcessor($objectMapper, $decorated);
        $this->assertSame($data, $processor->process($data, $operation));
    }

    public function testProcessBypassesWithNullData(): void
    {
        $operation = new Post(class: \stdClass::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with(null, $operation, [], [])
            ->willReturn(null);

        $processor = new ObjectMapperInputProcessor($objectMapper, $decorated);
        $this->assertNull($processor->process(null, $operation));
    }

    public function testProcessBypassesWithResponseData(): void
    {
        $response = new Response();
        $operation = new Post(class: \stdClass::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($response, $operation, [], [])
            ->willReturn($response);

        $processor = new ObjectMapperInputProcessor($objectMapper, $decorated);
        $this->assertSame($response, $processor->process($response, $operation));
    }

    public function testProcessBypassesWithMismatchedDataType(): void
    {
        $data = new \stdClass();
        $operation = new Post(class: ObjectMapperInputDummy::class);
        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], [])
            ->willReturn($data);

        $processor = new ObjectMapperInputProcessor($objectMapper, $decorated);
        $this->assertSame($data, $processor->process($data, $operation));
    }

    public function testProcessMapsInputToEntity(): void
    {
        $dto = new \stdClass();
        $entity = new \stdClass();
        $entity->id = 1;
        $result = new \stdClass();
        $operation = new Post(class: \stdClass::class, map: true);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($dto, null)
            ->willReturn($entity);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($entity, $operation, [], $this->anything())
            ->willReturn($result);

        $processor = new ObjectMapperInputProcessor($objectMapper, $decorated);
        $this->assertSame($result, $processor->process($dto, $operation));
    }

    public function testProcessMapsWithExistingMappedData(): void
    {
        $dto = new \stdClass();
        $existingEntity = new \stdClass();
        $existingEntity->id = 42;
        $mappedEntity = new \stdClass();
        $mappedEntity->id = 42;
        $result = new \stdClass();
        $operation = new Post(class: \stdClass::class, map: true);

        $request = new Request();
        $request->attributes->set('mapped_data', $existingEntity);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($dto, $existingEntity)
            ->willReturn($mappedEntity);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($mappedEntity, $operation, [], $this->anything())
            ->willReturn($result);

        $processor = new ObjectMapperInputProcessor($objectMapper, $decorated);
        $this->assertSame($result, $processor->process($dto, $operation, [], ['request' => $request]));
    }
}

class ObjectMapperInputDummy
{
}
