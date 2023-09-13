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
        $this->assertEquals($data, (new CallableProcessor())->process($data, $operation));
    }

    public function testCallable(): void
    {
        $operation = new Get(name: 'hello', processor: fn () => ['ok']);
        $this->assertEquals((new CallableProcessor())->process(new \stdClass(), $operation), ['ok']);
    }

    public function testCallableServiceLocator(): void
    {
        $operation = new Get(name: 'hello', processor: 'processor');
        $provider = $this->createMock(ProcessorInterface::class);
        $provider->method('process')->willReturn(['ok']);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('processor')->willReturn(true);
        $container->method('get')->with('processor')->willReturn($provider);
        $this->assertEquals((new CallableProcessor($container))->process(new \stdClass(), $operation), ['ok']);
    }

    public function testCallableServiceLocatorDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Processor "processor" not found on operation "hello"');
        $operation = new Get(name: 'hello', processor: 'processor');
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('processor')->willReturn(false);
        (new CallableProcessor($container))->process(new \stdClass(), $operation);
    }
}
