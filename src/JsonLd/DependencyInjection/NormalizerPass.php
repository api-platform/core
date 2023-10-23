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

namespace ApiPlatform\JsonLd\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NormalizerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('api_platform.jsonld.normalizer.item')) {
            $definition = $container->getDefinition('api_platform.jsonld.normalizer.item');
            $argument = '' === $definition->getArguments()[9] ? [] : $definition->getArguments()[9];
            $definition->setArgument(9, $argument);
        }
    }
}
