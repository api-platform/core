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

use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;

/**
 * Registers a dedicated {@see AttributeLoader} in the serializer mapping chain that always
 * loads metadata for api-platform's built-in error resources, regardless of the value of
 * `framework.serializer.enable_attributes`.
 *
 * When `enable_attributes: false`, Symfony's default attribute loader is built with
 * `allowAnyClass: false` and an empty mapped-classes list, so it returns early for every
 * class — including {@see Error} and {@see ValidationException}. Their serialization
 * groups never reach the metadata factory and the normalizer ends up producing an empty
 * payload for problem/hydra/json:api error responses (see issue #8174). Forcing a
 * targeted loader for these specific classes keeps error responses intact without
 * re-enabling global attribute discovery.
 *
 * @see https://github.com/api-platform/core/issues/8174
 */
final class ErrorResourceAttributeLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('serializer.mapping.chain_loader')) {
            return;
        }

        $mappedClasses = [
            Error::class => [Error::class],
            ValidationException::class => [ValidationException::class],
        ];

        $loaderDefinition = new Definition(AttributeLoader::class, [true, $mappedClasses]);

        $chainLoader = $container->getDefinition('serializer.mapping.chain_loader');
        $loaders = $chainLoader->getArgument(0);
        $loaders[] = $loaderDefinition;
        $chainLoader->replaceArgument(0, $loaders);

        if ($container->hasDefinition('serializer.mapping.cache_warmer')) {
            $cacheWarmer = $container->getDefinition('serializer.mapping.cache_warmer');
            $warmerLoaders = $cacheWarmer->getArgument(0);
            $warmerLoaders[] = $loaderDefinition;
            $cacheWarmer->replaceArgument(0, $warmerLoaders);
        }
    }
}
