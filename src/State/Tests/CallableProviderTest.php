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

use ApiPlatform\Metadata\Get;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CallableProviderTest extends TestCase
{
    public function testNoProvider(): void
    {
        $operation = new Get(name: 'hello');
        $this->expectException(ProviderNotFoundException::class);
        $this->expectExceptionMessage('Provider not found on operation "hello"');
        (new CallableProvider())->provide($operation);
    }

    public function testCallable(): void
    {
        $operation = new Get(name: 'hello', provider: fn () => ['ok']);
        $this->assertEquals((new CallableProvider())->provide($operation), ['ok']);
    }

    public function testCallableServiceLocator(): void
    {
        $operation = new Get(name: 'hello', provider: 'provider');
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('provide')->willReturn(['ok']);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('provider')->willReturn(true);
        $container->method('get')->with('provider')->willReturn($provider);
        $this->assertEquals((new CallableProvider($container))->provide($operation), ['ok']);
    }
}
