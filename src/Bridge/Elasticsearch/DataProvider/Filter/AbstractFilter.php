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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Util\FieldDatatypeTrait;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    use FieldDatatypeTrait { isNestedField as protected; }

    protected $properties;
    protected $propertyNameCollectionFactory;

    public function __construct(PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, array $properties = null)
    {
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
        $this->properties = $properties;
    }

    /**
     * Gets all enabled properties for the given resource class.
     */
    protected function getProperties(string $resourceClass): \Traversable
    {
        if (null === $properties = $this->properties) {
            try {
                $properties = iterator_to_array($this->propertyNameCollectionFactory->create($resourceClass));
            } catch (ResourceClassNotFoundException $e) {
                $properties = [];
            }
        }

        yield from $properties;
    }

    /**
     * Is the given property enabled?
     */
    protected function hasProperty(string $resourceClass, string $property): bool
    {
        return \in_array($property, iterator_to_array($this->getProperties($resourceClass)), true);
    }

    /**
     * Gets info about the decomposed given property for the given resource class.
     *
     * Returns an array with the following info as values:
     *   - the {@see Type} of the decomposed given property
     *   - is the decomposed given property an association?
     *   - the resource class of the decomposed given property
     *   - the property name of the decomposed given property
     */
    protected function getMetadata(string $resourceClass, string $property): array
    {
        if (!$this->hasProperty($resourceClass, $property)) {
            return array_fill(0, 4, null);
        }

        $properties = explode('.', $property);
        $totalProperties = \count($properties);
        $currentResourceClass = $resourceClass;
        $hasAssociation = false;

        foreach ($properties as $index => $currentProperty) {
            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($currentResourceClass, $currentProperty);
            } catch (PropertyNotFoundException $e) {
                return array_fill(0, 4, null);
            }

            if (null === $type = $propertyMetadata->getType()) {
                return array_fill(0, 4, null);
            }

            ++$index;
            $builtinType = $type->getBuiltinType();

            if (Type::BUILTIN_TYPE_OBJECT !== $builtinType && Type::BUILTIN_TYPE_ARRAY !== $builtinType) {
                if ($totalProperties === $index) {
                    break;
                }

                return array_fill(0, 4, null);
            }

            if ($type->isCollection() && null === $type = $type->getCollectionValueType()) {
                return array_fill(0, 4, null);
            }

            if (Type::BUILTIN_TYPE_ARRAY === $builtinType && Type::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
                if ($totalProperties === $index) {
                    break;
                }

                return array_fill(0, 4, null);
            }

            $currentResourceClass = $type->getClassName();

            if (null === $currentResourceClass || !$this->resourceClassResolver->isResourceClass($currentResourceClass)) {
                return array_fill(0, 4, null);
            }

            $hasAssociation = $totalProperties === $index;
        }

        return [$type, $hasAssociation, $currentResourceClass, $currentProperty];
    }
}
