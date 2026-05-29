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

/**
 * Bridges Symfony's public `property_info.*` tags to API Platform's private
 * `api_platform.property_info.*` tags so that `api_platform.property_info`
 * inherits framework- and third-party-registered extractors (e.g. Doctrine's
 * `DoctrineExtractor`) without API Platform's own extractors leaking back into
 * Symfony's `property_info` service.
 *
 * @internal
 *
 * @see https://github.com/api-platform/core/issues/8201
 */
final class PropertyInfoTagPass implements CompilerPassInterface
{
    private const TAG_SUFFIXES = [
        'list_extractor',
        'type_extractor',
        'description_extractor',
        'access_extractor',
        'initializable_extractor',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::TAG_SUFFIXES as $suffix) {
            $publicTag = 'property_info.'.$suffix;
            $privateTag = 'api_platform.property_info.'.$suffix;

            foreach ($container->findTaggedServiceIds($publicTag) as $serviceId => $tags) {
                $definition = $container->getDefinition($serviceId);
                if ($definition->hasTag($privateTag)) {
                    continue;
                }
                foreach ($tags as $attributes) {
                    $definition->addTag($privateTag, $attributes);
                }
            }
        }
    }
}
