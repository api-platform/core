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

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AuthenticatorManagerPass;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class AuthenticatorManagerPassTest extends TestCase
{
    use ProphecyTrait;

    private $containerBuilderProphecy;
    private $authenticatorManagerPass;

    protected function setUp(): void
    {
        $this->containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $this->authenticatorManagerPass = new AuthenticatorManagerPass();
    }

    public function testConstruct(): void
    {
        self::assertInstanceOf(CompilerPassInterface::class, $this->authenticatorManagerPass);
    }

    public function testProcessWithoutAuthenticatorManager(): void
    {
        $this->containerBuilderProphecy->has('security.authenticator.manager')->willReturn(false);
        $this->containerBuilderProphecy->getDefinition('api_platform.security.resource_access_checker')->shouldNotBeCalled();

        $this->authenticatorManagerPass->process($this->containerBuilderProphecy->reveal());
    }

    public function testProcess(): void
    {
        $this->containerBuilderProphecy->has('security.authenticator.manager')->willReturn(true);
        $authenticatorManagerDefinitionProphecy = $this->prophesize(Definition::class);
        $this->containerBuilderProphecy->getDefinition('api_platform.security.resource_access_checker')->willReturn($authenticatorManagerDefinitionProphecy->reveal());
        $authenticatorManagerDefinitionProphecy->setArgument(5, false)->shouldBeCalledOnce();

        $this->authenticatorManagerPass->process($this->containerBuilderProphecy->reveal());
    }
}
