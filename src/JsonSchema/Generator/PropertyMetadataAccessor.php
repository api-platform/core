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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\JsonSchema\Configuration;

final class PropertyMetadataAccessor
{
    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function contextFromConfiguration(Configuration $configuration): array
    {
        $context = [ConfigurationFactory::CONTEXT_PROPERTY_OPTIONS => $configuration->context[ConfigurationFactory::CONTEXT_PROPERTY_OPTIONS] ?? []];
        if ($configuration->groups) {
            $context['serializer_groups'] = $configuration->groups;
        }
        if (null !== $configuration->attributes) {
            $context['serializer_attributes'] = $configuration->attributes;
        }

        return $context;
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $context
     *
     * @return list<string>
     */
    public function getNames(string $class, array $context): array
    {
        return array_values(iterator_to_array($this->propertyNameCollectionFactory->create($class, self::options($context)), false));
    }

    /**
     * @param class-string         $class
     * @param array<string, mixed> $context
     */
    public function get(string $class, string $property, array $context): ApiProperty
    {
        return $this->propertyMetadataFactory->create($class, $property, self::options($context));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private static function options(array $context): array
    {
        $options = $context[ConfigurationFactory::CONTEXT_PROPERTY_OPTIONS] ?? [];
        $context = array_diff_key($context, array_flip(ConfigurationFactory::CONTEXT_KEYS));
        if (null === ($context['serializer_groups'] ?? null)) {
            unset($context['serializer_groups']);
        }

        return $options + $context;
    }
}
