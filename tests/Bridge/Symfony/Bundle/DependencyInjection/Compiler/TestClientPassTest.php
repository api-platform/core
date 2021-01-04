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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\TestClientPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class TestClientPassTest extends TestCase
{
    private $containerBuilderProphecy;
    private $testClientPass;

    protected function setUp(): void
    {
        $this->containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $this->testClientPass = new TestClientPass();
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(CompilerPassInterface::class, $this->testClientPass);
    }

    public function testProcessWithoutTestClientParameters(): void
    {
        $this->containerBuilderProphecy->hasParameter('test.client.parameters')->willReturn(false)->shouldBeCalledOnce();
        $this->containerBuilderProphecy->setDefinition('test.api_platform.client', Argument::type(Definition::class))->shouldNotBeCalled();

        $this->testClientPass->process($this->containerBuilderProphecy->reveal());
    }

    public function testProcess(): void
    {
        $this->containerBuilderProphecy->hasParameter('test.client.parameters')->willReturn(true)->shouldBeCalledOnce();
        $this->containerBuilderProphecy
            ->setDefinition(
                'test.api_platform.client',
                Argument::allOf(
                    Argument::type(Definition::class),
                    Argument::that(function (Definition $testClientDefinition) {
                        return
                            Client::class === $testClientDefinition->getClass() &&
                            !$testClientDefinition->isShared() &&
                            $testClientDefinition->isPublic();
                    })
                )
            )
            ->shouldBeCalledOnce();

        $this->testClientPass->process($this->containerBuilderProphecy->reveal());
    }
}
