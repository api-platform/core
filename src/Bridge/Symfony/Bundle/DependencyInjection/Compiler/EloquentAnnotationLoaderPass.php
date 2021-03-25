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

use ApiPlatform\Core\Bridge\Eloquent\Serializer\Mapping\Loader\AnnotationLoader as EloquentAnnotationLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;

/**
 * Decorate the Symfony annotation loader with the Eloquent one.
 *
 * @internal
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class EloquentAnnotationLoaderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('api_platform.eloquent.enabled')) {
            return;
        }

        $chainLoader = $container->getDefinition('serializer.mapping.chain_loader');

        $serializerLoaders = [];
        $serializerLoaderDefinitions = $chainLoader->getArgument(0);

        /** @var Definition $serializerLoaderDefinition */
        foreach ($serializerLoaderDefinitions as $serializerLoaderDefinition) {
            if (AnnotationLoader::class === $serializerLoaderDefinition->getClass()) {
                $serializerLoaders[] = new Definition(EloquentAnnotationLoader::class, [$serializerLoaderDefinition]);
                continue;
            }
            $serializerLoaders[] = $serializerLoaderDefinition;
        }

        $chainLoader->replaceArgument(0, $serializerLoaders);
    }
}
