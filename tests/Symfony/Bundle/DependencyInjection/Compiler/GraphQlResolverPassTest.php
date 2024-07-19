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

namespace ApiPlatform\Tests\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlResolverPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class GraphQlResolverPassTest extends TestCase
{
    public function testProcess(): void
    {
        $filterPass = new GraphQlResolverPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $filterPass);

        $typeLocatorDefinition = $this->createMock(Definition::class);
        $typeLocatorDefinition->expects($this->once())->method('addArgument')->with($this->callback(function () {
            return true;
        }));

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->once())->method('getParameter')->with('api_platform.graphql.enabled')->willReturn(true);
        $containerBuilder->method('findTaggedServiceIds')->willReturnOnConsecutiveCalls(
            [],
            [],
            ['foo' => [], 'bar' => [['id' => 'bar']]]
        );
        $containerBuilder->method('getDefinition')->with('api_platform.graphql.resolver_locator')->willReturn($typeLocatorDefinition);

        $filterPass->process($containerBuilder);
    }
}
