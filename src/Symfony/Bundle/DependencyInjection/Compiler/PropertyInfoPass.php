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

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Ensures the property_info service is always available.
 *
 * When symfony/framework-bundle has property_info disabled (which is the default
 * on a full-stack Symfony application unless explicitly opted-in), API Platform's
 * prependExtensionConfig() fallback can be overridden by user configuration.
 * This pass registers a minimal fallback so API Platform never fails with
 * "service not found" for property_info.
 *
 * @internal
 */
final class PropertyInfoPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('property_info') || $container->hasAlias('property_info')) {
            return;
        }

        if (!$container->hasDefinition('property_info.reflection_extractor')) {
            $reflectionExtractor = new Definition(ReflectionExtractor::class);
            $reflectionExtractor->addTag('property_info.list_extractor', ['priority' => -1000]);
            $reflectionExtractor->addTag('property_info.type_extractor', ['priority' => -1002]);
            $reflectionExtractor->addTag('property_info.access_extractor', ['priority' => -1000]);
            $reflectionExtractor->addTag('property_info.initializable_extractor', ['priority' => -1000]);
            $container->setDefinition('property_info.reflection_extractor', $reflectionExtractor);
        }

        $definition = new Definition(PropertyInfoExtractor::class);
        $definition->setArguments([
            new TaggedIteratorArgument('property_info.list_extractor'),
            new TaggedIteratorArgument('property_info.type_extractor'),
            new TaggedIteratorArgument('property_info.description_extractor'),
            new TaggedIteratorArgument('property_info.access_extractor'),
            new TaggedIteratorArgument('property_info.initializable_extractor'),
        ]);
        $container->setDefinition('property_info', $definition);
    }
}
