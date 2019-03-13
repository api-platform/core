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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle;

use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\AnnotationFilterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DataProviderPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\ElasticsearchClientPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlQueryResolverPass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\GraphQlTypePass;
use ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler\MetadataAwareNameConverterPass;
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
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DataProviderPass());
        $container->addCompilerPass(new AnnotationFilterPass());
        $container->addCompilerPass(new FilterPass());
        $container->addCompilerPass(new ElasticsearchClientPass());
        $container->addCompilerPass(new GraphQlTypePass());
        $container->addCompilerPass(new GraphQlQueryResolverPass());
        $container->addCompilerPass(new MetadataAwareNameConverterPass());
    }
}
