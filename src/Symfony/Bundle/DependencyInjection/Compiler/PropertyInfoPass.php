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

namespace ApiPlatform\Symfony\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

/**
 * Registers a ReflectionExtractor fallback for api_platform.property_info when
 * framework.property_info is disabled, so tagged_iterator('property_info.*') is never empty.
 *
 * @internal
 */
final class PropertyInfoPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('property_info.reflection_extractor')) {
            return;
        }

        $definition = new Definition(ReflectionExtractor::class);
        $definition->addTag('property_info.list_extractor', ['priority' => -1000]);
        $definition->addTag('property_info.type_extractor', ['priority' => -1002]);
        $definition->addTag('property_info.access_extractor', ['priority' => -1000]);
        $definition->addTag('property_info.initializable_extractor', ['priority' => -1000]);
        $container->setDefinition('api_platform.property_info.reflection_extractor', $definition);
    }
}
