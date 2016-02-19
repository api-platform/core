<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Symfony\Bundle;

use ApiPlatform\Builder\Bridge\Symfony\Bundle\DependencyInjection\Compiler\DoctrineQueryExtensionPass;
use ApiPlatform\Builder\Bridge\Symfony\Bundle\DependencyInjection\Compiler\FilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * ApiPlatformBuilderBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiPlatformBuilderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FilterPass());
        $container->addCompilerPass(new DoctrineQueryExtensionPass());
    }
}
