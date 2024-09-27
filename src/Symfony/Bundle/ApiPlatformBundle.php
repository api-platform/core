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

namespace ApiPlatform\Symfony\Bundle;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler\AttributeFilterPass;
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
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The Symfony bundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiPlatformBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DataProviderPass());
        // Run the compiler pass before the {@see ResolveInstanceofConditionalsPass} to allow autoconfiguration of generated filter definitions.
        $container->addCompilerPass(new AttributeFilterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 101);
        $container->addCompilerPass(new FilterPass());
        $container->addCompilerPass(new ElasticsearchClientPass());
        $container->addCompilerPass(new GraphQlTypePass());
        // These two are deprecated
        $container->addCompilerPass(new GraphQlQueryResolverPass());
        $container->addCompilerPass(new GraphQlMutationResolverPass());
        // We can use this one only in 4.0
        $container->addCompilerPass(new GraphQlResolverPass());
        $container->addCompilerPass(new MetadataAwareNameConverterPass());
        $container->addCompilerPass(new TestClientPass());
        $container->addCompilerPass(new TestMercureHubPass());
        $container->addCompilerPass(new AuthenticatorManagerPass());
        $container->addCompilerPass(new SerializerMappingLoaderPass());
    }
}
