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

namespace ApiPlatform\Elasticsearch\Util;

use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Exception\LogicException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Field datatypes helpers.
 *
 * @internal
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
trait FieldDatatypeTrait
{
    private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory;

    private readonly ResourceClassResolverInterface $resourceClassResolver;

    /**
     * Is the decomposed given property of the given resource class potentially mapped as a nested field in Elasticsearch?
     */
    private function isNestedField(string $resourceClass, string $property): bool
    {
        return null !== $this->getNestedFieldPath($resourceClass, $property);
    }

    /**
     * Get the nested path to the decomposed given property (e.g.: foo.bar.baz => foo.bar).
     *
     * Elasticsearch can save arrays of Objects as nested documents.
     * In the case of foo.bar.baz
     *   foo.bar will be returned if foo.bar is an array of objects.
     *   If neither foo nor bar is an array, it is not a nested property and will return null.
     */
    private function getNestedFieldPath(string $resourceClass, string $property): ?string
    {
        $properties = explode('.', $property);
        $currentProperty = array_shift($properties);

        if (!$properties) {
            return null;
        }

        try {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $currentProperty);
        } catch (PropertyNotFoundException) {
            return null;
        }

        $types = $propertyMetadata->getBuiltinTypes();

        if (!$types) {
            return null;
        }

        // BC layer for symfony/property-info < 7.1
        if (is_array($types)) {
            foreach ($types as $type) {
                if (
                    LegacyType::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
                    && null !== ($nextResourceClass = $type->getClassName())
                    && $this->resourceClassResolver->isResourceClass($nextResourceClass)
                ) {
                    $nestedPath = $this->getNestedFieldPath($nextResourceClass, implode('.', $properties));

                    return null === $nestedPath ? $nestedPath : "$currentProperty.$nestedPath";
                }

                if (
                    null !== ($type = $type->getCollectionValueTypes()[0] ?? null)
                    && LegacyType::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()
                    && null !== ($className = $type->getClassName())
                    && $this->resourceClassResolver->isResourceClass($className)
                ) {
                    $nestedPath = $this->getNestedFieldPath($className, implode('.', $properties));

                    return null === $nestedPath ? $currentProperty : "$currentProperty.$nestedPath";
                }
            }

            return null;
        }

        /** @var list<Type> $types */
        $types = $types instanceof UnionType || $types instanceof IntersectionType ? $types->getTypes() : [$types];

        foreach ($types as $type) {
            $type = $type->asNonNullable();

            try {
                $baseType = $type->getBaseType();
            } catch (LogicException) {
                continue;
            }

            if ($baseType instanceof ObjectType && $this->resourceClassResolver->isResourceClass($baseType->getClassName())) {
                $nestedPath = $this->getNestedFieldPath($baseType->getClassName(), implode('.', $properties));

                return null === $nestedPath ? $nestedPath : "$currentProperty.$nestedPath";
            }

            if ($type instanceof CollectionType) {
                $collectionValueType = $type->getCollectionValueType();

                try {
                    $collectionValueBaseType = $collectionValueType->asNonNullable()->getBaseType();
                } catch (LogicException) {
                    continue;
                }

                if ($collectionValueBaseType instanceof ObjectType && $this->resourceClassResolver->isResourceClass($collectionValueBaseType->getClassName())) {
                    $nestedPath = $this->getNestedFieldPath($collectionValueBaseType->getClassName(), implode('.', $properties));

                    return null === $nestedPath ? $currentProperty : "$currentProperty.$nestedPath";
                }
            }
        }

        return null;
    }
}
