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

use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('serializer.name_converter.metadata_aware')) {
            return;
        }

        $definition = $container->getDefinition('serializer.name_converter.metadata_aware');
        $key = '$fallbackNameConverter';
        $arguments = $definition->getArguments();
        if (false === \array_key_exists($key, $arguments)) {
            $key = 1;
        }

        if ($container->hasAlias('api_platform.name_converter')) {
            $nameConverter = new Reference((string) $container->getAlias('api_platform.name_converter'));

            // old symfony versions
            if (false === \array_key_exists($key, $arguments)) {
                $definition->addArgument($nameConverter);
            } elseif (null === $definition->getArgument($key)) {
                $definition->setArgument($key, $nameConverter);
            }
        }

        $container->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware');
    }
}
