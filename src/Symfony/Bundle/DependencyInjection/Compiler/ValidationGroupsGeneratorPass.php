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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects validation groups generators.
 *
 * @internal
 *
 * @author Danny van Wijk <dannyvanwijk@gmail.com>
 */
final class ValidationGroupsGeneratorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $generators = [];
        foreach ($container->findTaggedServiceIds('api_platform.validation_groups_generator', true) as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $generators[$tag['id'] ?? $serviceId] = new Reference($serviceId);
            }
        }

        $container->getDefinition('api_platform.validator_locator')->addArgument($generators);
    }
}
