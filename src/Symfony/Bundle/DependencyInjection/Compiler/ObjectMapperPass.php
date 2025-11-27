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
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ObjectMapper\ObjectMapper;

/**
 * Creates the Object Mapper.
 *
 * @author Florent Blaison <florent.blaison@gmail.com>
 */
final class ObjectMapperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!class_exists(ObjectMapper::class)) {
            return;
        }

        if ($container->has('object_mapper.metadata_factory')) {
            $container->setAlias('api_platform.object_mapper.metadata_factory', 'object_mapper.metadata_factory');
        } else {
            $container->setDefinition('api_platform.object_mapper.metadata_factory', new Definition('Symfony\Component\ObjectMapper\Metadata\ReflectionObjectMapperMetadataFactory'));
        }

        if ($container->has('object_mapper')) {
            $container->setAlias('api_platform.object_mapper', 'object_mapper');
        } else {
            $container->setDefinition('api_platform.object_mapper', new Definition('Symfony\Component\ObjectMapper\ObjectMapper', [
                new Reference('api_platform.object_mapper.metadata_factory'),
                new Reference('property_accessor', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new ServiceLocatorArgument(new TaggedIteratorArgument('object_mapper.transform_callable', null, null, true)),
                new ServiceLocatorArgument(new TaggedIteratorArgument('object_mapper.condition_callable', null, null, true)),
            ]));
        }
    }
}
