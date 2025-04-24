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
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

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

        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            $types = $propertyMetadata->getBuiltinTypes() ?? [];

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

        $type = $propertyMetadata->getNativeType();

        if (null === $type) {
            return null;
        }

        /** @var class-string|null $className */
        $className = null;

        $typeIsResourceClass = function (Type $type) use (&$className): bool {
            return $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($className = $type->getClassName());
        };

        if ($type->isSatisfiedBy($typeIsResourceClass)) {
            $nestedPath = $this->getNestedFieldPath($className, implode('.', $properties));

            return null === $nestedPath ? $nestedPath : "$currentProperty.$nestedPath";
        }

        if (TypeHelper::getCollectionValueType($type)?->isSatisfiedBy($typeIsResourceClass)) {
            $nestedPath = $this->getNestedFieldPath($className, implode('.', $properties));

            return null === $nestedPath ? $currentProperty : "$currentProperty.$nestedPath";
        }

        return null;
    }
}
