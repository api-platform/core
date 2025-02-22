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

use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers resource classes from {@see ApiResource} attribute.
 *
 * @internal
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class AttributeResourcePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $classes = $container->getParameter('api_platform.class_name_resources');

        // findTaggedServiceIds cannot be used, as the services are excluded
        foreach ($container->getDefinitions() as $definition) {
            if ($definition->hasTag('api_platform.resource')) {
                $classes[] = $definition->getClass();
            }
        }

        $container->setParameter('api_platform.class_name_resources', array_unique($classes));
    }
}
