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
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlResolverPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\JsonStreamerTransformerPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\MutatorPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\SerializerMappingLoaderPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\TestClientPass;
use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\TestMercureHubPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ApiPlatformBundleTest extends TestCase
{
    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        $bundle = new ApiPlatformBundle();
        $bundle->build($container);

        $passes = $container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map(static fn (object $p): string => $p::class, $passes);

        // TODO: remove in 5.x
        $this->assertContains(DataProviderPass::class, $passClasses);
        $this->assertContains(AttributeFilterPass::class, $passClasses);
        $this->assertContains(AttributeResourcePass::class, $passClasses);
        $this->assertContains(FilterPass::class, $passClasses);
        $this->assertContains(ElasticsearchClientPass::class, $passClasses);
        $this->assertContains(GraphQlTypePass::class, $passClasses);
        $this->assertContains(GraphQlResolverPass::class, $passClasses);
        $this->assertContains(MetadataAwareNameConverterPass::class, $passClasses);
        $this->assertContains(TestClientPass::class, $passClasses);
        $this->assertContains(TestMercureHubPass::class, $passClasses);
        $this->assertContains(AuthenticatorManagerPass::class, $passClasses);
        $this->assertContains(SerializerMappingLoaderPass::class, $passClasses);
        $this->assertContains(MutatorPass::class, $passClasses);
        $this->assertContains(JsonStreamerTransformerPass::class, $passClasses);
    }
}
