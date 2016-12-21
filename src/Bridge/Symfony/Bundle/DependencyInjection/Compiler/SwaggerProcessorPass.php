<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Swagger\Extractor\SwaggerOperationExtractorInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects swagger extractors.
 *
 * @internal
 *
 * @author Piotr Brzezina <piotr@g1net.pl>
 */
final class SwaggerProcessorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(ContainerBuilder $container)
    {
        // if doctrine not loaded
        if (!$container->hasDefinition('api_platform.swagger.processor.swagger_extractor_processor')) {
            return;
        }
        $extractors = $this->findAndSortTaggedServices('api_platform.swagger_extractor', $container);
        $container->getDefinition('api_platform.swagger.processor.swagger_extractor_processor')->addArgument($extractors);
    }

    /**
     * The priority sorting algorithm has been backported from Symfony 3.2.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php
     *
     * @param string           $tagName
     * @param ContainerBuilder $container
     *
     * @throws RuntimeException
     *
     * @return array
     */
    private function findAndSortTaggedServices(string $tagName, ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds($tagName);
        $queue = new \SplPriorityQueue();
        foreach ($services as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $reflection = new \ReflectionClass($definition->getClass());
            if (!$reflection->implementsInterface(SwaggerOperationExtractorInterface::class)) {
                throw new InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $serviceId, SwaggerOperationExtractorInterface::class));
            }
            foreach ($tags as $attributes) {
                $priority = isset($attributes['priority']) ? $attributes['priority'] : 0;
                $queue->insert(new Reference($serviceId), $priority);
            }
        }

        return iterator_to_array($queue, false);
    }
}
