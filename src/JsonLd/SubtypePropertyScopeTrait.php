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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
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
     * @param list<string>         $resourcePropertyNames
     * @param array<string, mixed> $propertyNameContext
     * @param array<string, mixed> $propertyContext
     *
     * @return array{array<class-string, array<string, array{class-string, ApiProperty|null}>>, array<string, string|list<string>>, array<class-string, array<string, true>>} the scope and the metadata (null for a type property) of each property declared by a subtype, the explicit IRIs of these properties, the type properties
     */
    private function getSubtypePropertyScopes(SubtypeResolverInterface $subtypeResolver, PropertyMetadataFactoryInterface $propertyMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, string $resourceClass, array $resourcePropertyNames, array $propertyNameContext, array $propertyContext): array
    {
        $declared = [$resourceClass => array_fill_keys($resourcePropertyNames, true)];
        if (null !== $resourceTypeProperty = $subtypeResolver->getMapping($resourceClass)?->getTypeProperty()) {
            $declared[$resourceClass][$resourceTypeProperty] = true;
        }

        $typeProperties = [];
        $declarers = [];
        foreach ($subtypeResolver->getSubtypes($resourceClass) as $subtype) {
            $declared[$subtype] = [];
            foreach ($propertyNameCollectionFactory->create($subtype, $propertyNameContext) as $propertyName) {
                $declared[$subtype][$propertyName] = true;
            }

            if (null !== ($typeProperty = $subtypeResolver->getMapping($subtype)?->getTypeProperty()) && !isset($declared[$subtype][$typeProperty])) {
                $declared[$subtype][$typeProperty] = $typeProperties[$subtype][$typeProperty] = true;
            }

            $ancestors = [];
            foreach ($declared as $class => $_) {
                if ($class !== $subtype && is_a($subtype, $class, true)) {
                    $ancestors[] = $class;
                }
            }

            foreach ($declared[$subtype] as $propertyName => $_) {
                foreach ($ancestors as $ancestor) {
                    if (isset($declared[$ancestor][$propertyName])) {
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
                if (isset($typeProperties[$subtype][$propertyName])) {
                    $scopes[$subtype][$propertyName] = [$scope, null];

                    continue;
                }

                $propertyMetadata = $propertyMetadataFactory->create($subtype, $propertyName, $propertyContext);
                $scopes[$subtype][$propertyName] = [$scope, $propertyMetadata];
                if (!$propertyIris = $propertyMetadata->getIris()) {
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
