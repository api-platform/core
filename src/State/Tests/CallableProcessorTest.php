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

namespace ApiPlatform\State\Tests;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\State\CallableProcessor;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CallableProcessorTest extends TestCase
{
    public function testNoProcessor(): void
    {
        $operation = new Get(name: 'hello');
        $data = new \stdClass();
        $this->assertSame($data, (new CallableProcessor())->process($data, $operation));
    }

    public function testCallable(): void
    {
        $operation = new Get(name: 'hello', processor: static fn () => ['ok']);
        $this->assertSame((new CallableProcessor())->process(new \stdClass(), $operation), ['ok']);
    }

    public function testCallableServiceLocator(): void
    {
        $operation = new Get(name: 'hello', processor: 'processor');
        $provider = $this->createMock(ProcessorInterface::class);
        $provider->method('process')->willReturn(['ok']);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['processor', true]]);
        $container->method('get')->willReturnMap([['processor', $provider]]);
        $this->assertSame((new CallableProcessor($container))->process(new \stdClass(), $operation), ['ok']);
    }

    public function testCallableServiceLocatorDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Processor "processor" not found on operation "hello"');
        $operation = new Get(name: 'hello', processor: 'processor');
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnMap([['processor', false]]);
        (new CallableProcessor($container))->process(new \stdClass(), $operation);
    }
}
