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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Detaches an item denormalizer from the serializer chain when a user decorator of the matching normalizer implements {@see DenormalizerInterface}.
 *
 * @internal
 *
 * @todo remove in 6.0
 */
final class ItemNormalizerDecorationBcPass implements CompilerPassInterface
{
    private const PAIRS = [
        'api_platform.serializer.normalizer.item' => 'api_platform.serializer.denormalizer.item',
        'api_platform.jsonld.normalizer.item' => 'api_platform.jsonld.denormalizer.item',
        'api_platform.jsonapi.normalizer.item' => 'api_platform.jsonapi.denormalizer.item',
        'api_platform.graphql.normalizer.item' => 'api_platform.graphql.denormalizer.item',
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::PAIRS as $normalizerId => $denormalizerId) {
            if (!$container->hasDefinition($normalizerId) || !$container->hasDefinition($denormalizerId)) {
                continue;
            }

            $decorators = $this->findUserDecorators($container, $normalizerId);
            if ([] === $decorators || [] !== $this->findUserDecorators($container, $denormalizerId, false)) {
                continue;
            }

            $container->getDefinition($denormalizerId)->clearTag('serializer.normalizer');

            foreach ($decorators as $id) {
                trigger_deprecation('api-platform/core', '4.4', 'Service "%s" decorates "%s" and implements "%s": denormalization is routed through the decorated normalizer for backward compatibility. Decorate "%s" instead.', $id, $normalizerId, DenormalizerInterface::class, $denormalizerId);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function findUserDecorators(ContainerBuilder $container, string $decoratedId, bool $onlyDenormalizers = true): array
    {
        $ids = [];
        foreach ($container->getDefinitions() as $id => $definition) {
            if (str_starts_with((string) $id, 'api_platform.') || ($definition->getDecoratedService()[0] ?? null) !== $decoratedId) {
                continue;
            }

            if ($onlyDenormalizers && !$this->implementsDenormalizer($container, $definition->getClass())) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }

    private function implementsDenormalizer(ContainerBuilder $container, ?string $class): bool
    {
        if (null === $class) {
            return false;
        }

        $class = $container->getParameterBag()->resolveValue($class);

        return \is_string($class) && true === $container->getReflectionClass($class, false)?->implementsInterface(DenormalizerInterface::class);
    }
}
