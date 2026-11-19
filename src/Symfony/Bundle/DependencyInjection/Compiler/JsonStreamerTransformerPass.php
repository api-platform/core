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

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\JsonStreamer\Transformer\ValueObjectTransformerInterface;

/**
 * Builds a transformers locator merging "json_streamer.property_value_transformer",
 * "json_streamer.value_transformer" (legacy) and "json_streamer.value_object_transformer"
 * services, and assigns it to API Platform's custom JSON-LD stream reader/writer.
 *
 * FrameworkBundle's own TransformerPass only touches the standard json_streamer.stream_reader/writer
 * services, not API Platform's JSON-LD-scoped ones; see https://github.com/symfony/symfony/pull/64190
 * for a proposed upstream fix that would make this pass obsolete.
 *
 * @internal
 */
final class JsonStreamerTransformerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!interface_exists(ValueObjectTransformerInterface::class)) {
            return;
        }

        if (!$container->hasDefinition('api_platform.jsonld.json_streamer.stream_reader')
            && !$container->hasDefinition('api_platform.jsonld.json_streamer.stream_writer')) {
            return;
        }

        $map = [];

        foreach (['json_streamer.property_value_transformer', 'json_streamer.value_transformer'] as $tagName) {
            foreach ($container->findTaggedServiceIds($tagName, true) as $id => $_) {
                $map[$id] ??= new Reference($id);
            }
        }

        foreach ($container->findTaggedServiceIds('json_streamer.value_object_transformer', true) as $id => $_) {
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass());
            if (!\is_string($class) || !method_exists($class, 'getValueObjectClassName')) {
                continue;
            }

            $map[$class::getValueObjectClassName()] = new Reference($id);
        }

        $argument = new ServiceLocatorArgument($map);

        foreach (['api_platform.jsonld.json_streamer.stream_reader', 'api_platform.jsonld.json_streamer.stream_writer'] as $serviceId) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->replaceArgument(0, $argument);
            }
        }
    }
}
