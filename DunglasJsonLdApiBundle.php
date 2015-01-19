<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle;

use Dunglas\JsonLdApiBundle\DependencyInjection\Compiler\ResourcesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * DunglasJsonLdApiBundle.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DunglasJsonLdApiBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ResourcesCompilerPass());
    }
}
