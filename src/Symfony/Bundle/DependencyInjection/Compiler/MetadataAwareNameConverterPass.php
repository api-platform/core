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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;

/**
 * Creates API Platform's own metadata-aware name converter to avoid polluting Symfony's global serializer.
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
        if (!$container->hasDefinition('serializer.mapping.class_metadata_factory')) {
            return;
        }

        $fallbackConverter = null;

        // Check if user explicitly configured a name converter for API Platform
        if ($container->hasAlias('api_platform.name_converter')) {
            $fallbackConverter = new Reference((string) $container->getAlias('api_platform.name_converter'));
        }

        // Create API Platform's own metadata-aware name converter (isolated from Symfony's)
        $definition = new Definition(MetadataAwareNameConverter::class, [
            new Reference('serializer.mapping.class_metadata_factory'),
            $fallbackConverter,
        ]);

        $container->setDefinition('api_platform.name_converter.metadata_aware', $definition);
        $container->setAlias('api_platform.name_converter', 'api_platform.name_converter.metadata_aware');
    }
}
