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
use ApiPlatform\State\Processor\ObjectMapperOutputProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

class ObjectMapperOutputProcessorTest extends TestCase
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

        $processor = new ObjectMapperOutputProcessor(null, $decorated);
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

        $processor = new ObjectMapperOutputProcessor($objectMapper, $decorated);
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

        $processor = new ObjectMapperOutputProcessor($objectMapper, $decorated);
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

        $processor = new ObjectMapperOutputProcessor($objectMapper, $decorated);
        $this->assertSame($response, $processor->process($response, $operation));
    }

    public function testProcessMapsEntityToDto(): void
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $dto = new \stdClass();
        $dto->id = 1;
        $result = new \stdClass();
        $operation = new Post(class: ObjectMapperOutputDummy::class, map: true);

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($entity, ObjectMapperOutputDummy::class)
            ->willReturn($dto);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->with($dto, $operation, [], $this->anything())
            ->willReturn($result);

        $processor = new ObjectMapperOutputProcessor($objectMapper, $decorated);
        $this->assertSame($result, $processor->process($entity, $operation));
    }

    public function testProcessSetsPersistedDataOnRequest(): void
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $dto = new \stdClass();
        $operation = new Post(class: ObjectMapperOutputDummy::class, map: true);
        $request = new Request();

        $objectMapper = $this->createMock(ObjectMapperInterface::class);
        $objectMapper->expects($this->once())
            ->method('map')
            ->with($entity, ObjectMapperOutputDummy::class)
            ->willReturn($dto);

        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated->expects($this->once())
            ->method('process')
            ->willReturn($dto);

        $processor = new ObjectMapperOutputProcessor($objectMapper, $decorated);
        $processor->process($entity, $operation, [], ['request' => $request]);

        $this->assertSame($entity, $request->attributes->get('persisted_data'));
    }
}

class ObjectMapperOutputDummy
{
}
