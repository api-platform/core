<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Remove the definition of the "twig.exception_listener" service when Twig is enabled.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
class TwigExceptionListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->has('api.hydra.listener.exception') && $container->has('twig.exception_listener')) {
            $container->removeDefinition('twig.exception_listener');
        }
    }
}
