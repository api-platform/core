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

namespace ApiPlatform\GraphQl\Tests\State\Provider;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\GraphQl\State\Provider\ResolverProvider;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ResolverProviderTest extends TestCase
{
    public function testProvide(): void
    {
        $res = new \stdClass();
        $operation = new QueryCollection(class: 'dummy', resolver: 'foo');
        $context = [];
        $decorated = $this->createMock(ProviderInterface::class);
        $resolverMock = $this->createMock(QueryItemResolverInterface::class);
        $resolverMock->expects($this->once())->method('__invoke')->willReturn($res);
        $resolverLocator = $this->createMock(ContainerInterface::class);
        $resolverLocator->expects($this->once())->method('get')->with('foo')->willReturn($resolverMock);
        $provider = new ResolverProvider($decorated, $resolverLocator);
        $this->assertEquals($res, $provider->provide($operation, [], $context));
    }
}
