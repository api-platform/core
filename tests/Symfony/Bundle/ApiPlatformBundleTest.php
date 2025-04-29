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

namespace ApiPlatform\Tests\Symfony\Bundle;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AttributeFilterPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AttributeResourcePass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AuthenticatorManagerPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlMutationResolverPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlQueryResolverPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlResolverPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\SerializerMappingLoaderPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\TestClientPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\TestMercureHubPass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformBundleTest extends TestCase
{
    use ProphecyTrait;

    public function testBuild(): void
    {
        $containerProphecy = $this->prophesize(ContainerBuilder::class);
        $containerProphecy->addCompilerPass(Argument::type(DataProviderPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(AttributeFilterPass::class), PassConfig::TYPE_BEFORE_OPTIMIZATION, 101)->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(AttributeResourcePass::class))->shouldBeCalled()->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(FilterPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(ElasticsearchClientPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(GraphQlTypePass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(GraphQlQueryResolverPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(GraphQlMutationResolverPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(GraphQlResolverPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(MetadataAwareNameConverterPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(TestClientPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(TestMercureHubPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(AuthenticatorManagerPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();
        $containerProphecy->addCompilerPass(Argument::type(SerializerMappingLoaderPass::class))->willReturn($containerProphecy->reveal())->shouldBeCalled();

        $bundle = new ApiPlatformBundle();
        $bundle->build($containerProphecy->reveal());
    }
}
