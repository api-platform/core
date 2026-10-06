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

namespace ApiPlatform\Metadata\Util;

use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;

/**
 * Walks the (possibly nested) serializer discriminator maps of a class.
 *
 * Only strict subclasses declared in a map are considered as subtypes: walking down nested maps
 * always descends the class hierarchy, which guarantees termination even with misconfigured maps.
 *
 * @internal
 */
final class ClassDiscriminatorHelper
{
    /**
     * Returns the subtypes declared in the given map of a class, indexed by their type value.
     *
     * @return array<string, class-string>
     */
    public static function getDirectSubtypes(ClassDiscriminatorMapping $mapping, string $class): array
    {
        $subtypes = [];
        foreach ($mapping->getTypesMapping() as $typeValue => $typeClass) {
            if ($typeClass !== $class && is_a($typeClass, $class, true)) {
                $subtypes[(string) $typeValue] = $typeClass;
            }
        }

        return $subtypes;
    }

    /**
     * Returns the type values the given map assigns to the class itself.
     *
     * @return list<string>
     */
    public static function getOwnTypes(ClassDiscriminatorMapping $mapping, string $class): array
    {
        $types = [];
        foreach ($mapping->getTypesMapping() as $typeValue => $typeClass) {
            if ($typeClass === $class) {
                $types[] = (string) $typeValue;
            }
        }

        return $types;
    }

    /**
     * Lists the subtypes declared, possibly through nested maps, in the discriminator map of a class.
     *
     * @return list<class-string>
     */
    public static function getSubtypes(ClassDiscriminatorResolverInterface $resolver, string $class): array
    {
        if (null === $mapping = $resolver->getMappingForClass($class)) {
            return [];
        }

        $subtypes = [];
        foreach (self::getDirectSubtypes($mapping, $class) as $subtype) {
            $subtypes[] = $subtype;
            foreach (self::getSubtypes($resolver, $subtype) as $nestedSubtype) {
                $subtypes[] = $nestedSubtype;
            }
        }

        return array_values(array_unique($subtypes));
    }

    /**
     * Resolves the most specific class declared in the discriminator maps of a resource for the given class.
     *
     * Classes that are not part of a discriminator map resolve to their closest mapped parent.
     * A class declaring itself in its own map carries the type property of that map too.
     *
     * @param class-string $resourceClass
     * @param class-string $class
     *
     * @return array{class: class-string, type_properties: list<string>, mappings: list<ClassDiscriminatorMapping>}|null
     */
    public static function resolve(ClassDiscriminatorResolverInterface $resolver, string $resourceClass, string $class): ?array
    {
        if (!is_a($class, $resourceClass, true)) {
            return null;
        }

        $mappedClass = $resourceClass;
        $typeProperties = [];
        $mappings = [];
        while (null !== $mapping = $resolver->getMappingForClass($mappedClass)) {
            $candidate = null;
            foreach (self::getDirectSubtypes($mapping, $mappedClass) as $typeClass) {
                if (is_a($class, $typeClass, true) && (null === $candidate || is_a($typeClass, $candidate, true))) {
                    $candidate = $typeClass;
                }
            }

            if (null === $candidate && !self::getOwnTypes($mapping, $mappedClass)) {
                break;
            }

            $typeProperties[] = $mapping->getTypeProperty();
            $mappings[] = $mapping;
            if (null === $candidate) {
                break;
            }

            $mappedClass = $candidate;
        }

        if (!$mappings) {
            return null;
        }

        return ['class' => $mappedClass, 'type_properties' => array_values(array_unique($typeProperties)), 'mappings' => $mappings];
    }
}
