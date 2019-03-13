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

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Injects the metadata aware name converter if available.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class MetadataAwareNameConverterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasAlias('api_platform.name_converter') || !$container->hasDefinition('serializer.name_converter.metadata_aware')) {
            return;
        }

        $definition = $container->getDefinition('serializer.name_converter.metadata_aware');

        if (1 >= \count($definition->getArguments()) || null === $definition->getArgument(1)) {
            return;
        }

        $container->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware');
    }
}
