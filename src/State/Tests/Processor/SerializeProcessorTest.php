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
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class SerializeProcessorTest extends TestCase
{
    public function testHeadRequestSkipsSerializationAndForwardsNull(): void
    {
        $request = Request::create('/foos', 'HEAD');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->never())->method('serialize');

        $inner = $this->createMock(ProcessorInterface::class);
        $inner->expects($this->once())
            ->method('process')
            ->with($this->isNull())
            ->willReturn(null);

        $processor = new SerializeProcessor($inner, $serializer, $this->createStub(SerializerContextBuilderInterface::class));
        $operation = (new Get())->withSerialize(true);

        $this->assertNull($processor->process(new \stdClass(), $operation, [], ['request' => $request]));
    }

    public function testHeadRequestSerializesWhenOptimizationDisabled(): void
    {
        $request = Request::create('/foos', 'HEAD');

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())->method('serialize')->willReturn('');

        $inner = $this->createMock(ProcessorInterface::class);
        $inner->method('process')->willReturn('forwarded');

        $contextBuilder = $this->createStub(SerializerContextBuilderInterface::class);
        $contextBuilder->method('createFromRequest')->willReturn([]);

        $processor = new SerializeProcessor($inner, $serializer, $contextBuilder, false);
        $operation = (new Get())->withSerialize(true);

        $this->assertSame('forwarded', $processor->process(new \stdClass(), $operation, [], ['request' => $request]));
    }
}
