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

namespace ApiPlatform\JsonLd;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\SubtypeResolverInterface;

/**
 * Scopes the properties the subtypes of a resource declare, shared by the JSON-LD context and the Hydra documentation.
 *
 * A property is declared by a subtype when no mapped parent declares it. Declared by several subtypes, it is one
 * property scoped to their closest common mapped parent. The type property of a map is declared by the class of the map.
 *
 * @internal
 */
trait SubtypePropertyScopeTrait
{
    /**
     * @param list<string>                         $resourcePropertyNames
     * @param callable(class-string): list<string> $propertyNames
     *
     * @return array{array<class-string, array<string, class-string>>, array<string, string|list<string>>, array<class-string, array<string, true>>} the scope of each property declared by a subtype, the explicit IRIs of these properties, the type properties
     */
    private function getSubtypePropertyScopes(SubtypeResolverInterface $subtypeResolver, PropertyMetadataFactoryInterface $propertyMetadataFactory, string $resourceClass, array $resourcePropertyNames, callable $propertyNames): array
    {
        $declared = [$resourceClass => array_fill_keys($resourcePropertyNames, true)];
        if (null !== $resourceTypeProperty = $subtypeResolver->getMapping($resourceClass)?->getTypeProperty()) {
            $declared[$resourceClass][$resourceTypeProperty] = true;
        }

        $typeProperties = [];
        $declarers = [];
        foreach ($subtypeResolver->getSubtypes($resourceClass) as $subtype) {
            $declared[$subtype] = array_fill_keys($propertyNames($subtype), true);
            if (null !== ($typeProperty = $subtypeResolver->getMapping($subtype)?->getTypeProperty()) && !isset($declared[$subtype][$typeProperty])) {
                $declared[$subtype][$typeProperty] = $typeProperties[$subtype][$typeProperty] = true;
            }

            foreach ($declared[$subtype] as $propertyName => $_) {
                foreach ($declared as $class => $classPropertyNames) {
                    if ($class !== $subtype && isset($classPropertyNames[$propertyName]) && is_a($subtype, $class, true)) {
                        continue 2;
                    }
                }

                $declarers[$propertyName][] = $subtype;
            }
        }

        $scopes = [];
        $iris = [];
        foreach ($declarers as $propertyName => $subtypes) {
            $scope = $subtypes[0];
            if (\count($subtypes) > 1) {
                $scope = $resourceClass;
                foreach (array_keys($declared) as $class) {
                    if (is_a($class, $scope, true) && $this->isParentOfAll($class, $subtypes)) {
                        $scope = $class;
                    }
                }
            }

            $irisOwner = null;
            foreach ($subtypes as $subtype) {
                $scopes[$subtype][$propertyName] = $scope;
                if (isset($typeProperties[$subtype][$propertyName]) || !$propertyIris = $propertyMetadataFactory->create($subtype, $propertyName)->getIris()) {
                    continue;
                }

                $propertyIris = 1 === \count($propertyIris) ? $propertyIris[0] : $propertyIris;
                if (null !== $irisOwner && $iris[$propertyName] !== $propertyIris) {
                    throw new RuntimeException(\sprintf('The subtypes "%s" and "%s" of the resource "%s" give the "%s" property different "iris": declare the same ones or rename one of the properties.', $irisOwner, $subtype, $resourceClass, $propertyName));
                }

                $irisOwner ??= $subtype;
                $iris[$propertyName] = $propertyIris;
            }
        }

        return [$scopes, $iris, $typeProperties];
    }

    /**
     * @param list<class-string> $classes
     */
    private function isParentOfAll(string $parent, array $classes): bool
    {
        foreach ($classes as $class) {
            if (!is_a($class, $parent, true)) {
                return false;
            }
        }

        return true;
    }
}
