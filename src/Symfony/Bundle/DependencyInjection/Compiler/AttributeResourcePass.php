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
        $resourceClassDirectories = $container->getParameter('api_platform.resource_class_directories');

        // findTaggedServiceIds cannot be used, as the services are excluded
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->hasTag('api_platform.resource')) {
                $r = new \ReflectionClass($definition->getClass());
                if ($r->getFileName()) {
                    $resourceClassDirectories[] = \dirname($r->getFileName());
                }
            }
        }
        $resourceClassDirectories = array_unique($resourceClassDirectories);
        $container->setParameter('api_platform.resource_class_directories', $resourceClassDirectories);
    }
}
