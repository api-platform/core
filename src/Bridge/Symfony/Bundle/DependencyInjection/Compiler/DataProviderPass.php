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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DependencyInjection\Compiler;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\DataProvider\SerializerAwareDataProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers data providers.
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class DataProviderPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach (OperationType::TYPES as $type) {
            $this->registerDataProviders($container, $type);
        }
    }

    /**
     * The priority sorting algorithm has been backported from Symfony 3.2.
     *
     * @see https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Compiler/PriorityTaggedServiceTrait.php
     *
     * @param ContainerBuilder $container
     * @param string           $type
     */
    private function registerDataProviders(ContainerBuilder $container, string $type)
    {
        $services = $this->findAndSortTaggedServices("api_platform.{$type}_data_provider", $container);

        foreach ($services as $reference) {
            $definition = $container->getDefinition((string) $reference);
            if (is_a($definition->getClass(), SerializerAwareDataProviderInterface::class, true)) {
                $definition->addMethodCall('setSerializerLocator', [new Reference('api_platform.serializer_locator')]);
            }
        }

        $container->getDefinition("api_platform.{$type}_data_provider")->addArgument($services);
    }
}
