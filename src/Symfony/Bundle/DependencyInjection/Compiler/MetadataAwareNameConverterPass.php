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
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
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
     * @throws InvalidArgumentException
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('serializer.name_converter.metadata_aware')) {
            return;
        }

        if ($container->hasAlias('api_platform.name_converter')) {
            $nameConverter = (string) $container->getAlias('api_platform.name_converter');

            $container->setParameter('.serializer.name_converter', $nameConverter);
            $container->getDefinition('serializer.name_converter.metadata_aware')->setArgument(1, new Reference($nameConverter));
        }

        $container->setAlias('api_platform.name_converter', 'serializer.name_converter.metadata_aware');
    }
}
