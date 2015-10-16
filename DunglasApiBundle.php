<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle;

use Dunglas\ApiBundle\DependencyInjection\Compiler\DataProviderPass;
use Dunglas\ApiBundle\DependencyInjection\Compiler\DoctrineQueryExtensionPass;
use Dunglas\ApiBundle\DependencyInjection\Compiler\ResourcePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * DunglasApiBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DunglasApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ResourcePass());
        $container->addCompilerPass(new DataProviderPass());
        $container->addCompilerPass(new DoctrineQueryExtensionPass());
    }
}
