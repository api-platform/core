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

namespace ApiPlatform\Elasticsearch\Filter;

use ApiPlatform\Elasticsearch\Util\FieldDatatypeTrait;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;

/**
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    use FieldDatatypeTrait {
        getNestedFieldPath as protected;
    }

    public function __construct(protected PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, ResourceClassResolverInterface $resourceClassResolver, protected ?NameConverterInterface $nameConverter = null, protected ?array $properties = null)
    {
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->resourceClassResolver = $resourceClassResolver;
    }

    /**
     * Gets all enabled properties for the given resource class.
     */
    protected function getProperties(string $resourceClass): \Traversable
    {
        if (null !== $this->properties) {
            return yield from array_keys($this->properties);
        }

        try {
            yield from $this->propertyNameCollectionFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException) {
        }
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
     *
     * @return array{0: ?Type, 1: ?bool, 2: ?class-string, 3: ?string}
     */
    protected function getMetadata(string $resourceClass, string $property): array
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) {
            return $this->getLegacyMetadata($resourceClass, $property);
        }

        $noop = [null, null, null, null];

        if (!$this->hasProperty($resourceClass, $property)) {
            return $noop;
        }

        $properties = explode('.', $property);
        $totalProperties = \count($properties);
        $currentResourceClass = $resourceClass;
        $hasAssociation = false;
        $currentProperty = null;
        $type = null;

        foreach ($properties as $index => $currentProperty) {
            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($currentResourceClass, $currentProperty);
            } catch (PropertyNotFoundException) {
                return $noop;
            }

            // check each type before deciding if it's noop or not
            // e.g: maybe the first type is noop, but the second is valid
            $isNoop = false;

            ++$index;

            $type = $propertyMetadata->getNativeType();

            if (null === $type) {
                return $noop;
            }

            foreach ($type instanceof CompositeTypeInterface ? $type->getTypes() : [$type] as $t) {
                $builtinType = $t;

                while ($builtinType instanceof WrappingTypeInterface) {
                    $builtinType = $builtinType->getWrappedType();
                }

                if (!$builtinType instanceof ObjectType && !$t instanceof CollectionType) {
                    if ($totalProperties === $index) {
                        break 2;
                    }

                    $isNoop = true;

                    continue;
                }

                if ($t instanceof CollectionType) {
                    $t = $t->getCollectionValueType();
                    $builtinType = $t;

                    while ($builtinType instanceof WrappingTypeInterface) {
                        $builtinType = $builtinType->getWrappedType();
                    }

                    if (!$builtinType instanceof ObjectType) {
                        if ($totalProperties === $index) {
                            break 2;
                        }

                        $isNoop = true;

                        continue;
                    }
                }

                $className = $builtinType->getClassName();

                if ($isResourceClass = $this->resourceClassResolver->isResourceClass($className)) {
                    $currentResourceClass = $className;
                } elseif ($totalProperties !== $index) {
                    $isNoop = true;

                    continue;
                }

                $hasAssociation = $totalProperties === $index && $isResourceClass;
                $isNoop = false;

                break;
            }
        }

        if ($isNoop) {
            return $noop;
        }

        return [$type, $hasAssociation, $currentResourceClass, $currentProperty];
    }

    protected function getLegacyMetadata(string $resourceClass, string $property): array
    {
        $noop = [null, null, null, null];

        if (!$this->hasProperty($resourceClass, $property)) {
            return $noop;
        }

        $properties = explode('.', $property);
        $totalProperties = \count($properties);
        $currentResourceClass = $resourceClass;
        $hasAssociation = false;
        $currentProperty = null;
        $type = null;

        foreach ($properties as $index => $currentProperty) {
            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($currentResourceClass, $currentProperty);
            } catch (PropertyNotFoundException) {
                return $noop;
            }

            $types = $propertyMetadata->getBuiltinTypes();

            if (null === $types) {
                return $noop;
            }

            ++$index;

            // check each type before deciding if it's noop or not
            // e.g: maybe the first type is noop, but the second is valid
            $isNoop = false;

            foreach ($types as $type) {
                $builtinType = $type->getBuiltinType();

                if (LegacyType::BUILTIN_TYPE_OBJECT !== $builtinType && LegacyType::BUILTIN_TYPE_ARRAY !== $builtinType) {
                    if ($totalProperties === $index) {
                        break 2;
                    }

                    $isNoop = true;

                    continue;
                }

                if ($type->isCollection() && null === $type = $type->getCollectionValueTypes()[0] ?? null) {
                    $isNoop = true;

                    continue;
                }

                if (LegacyType::BUILTIN_TYPE_ARRAY === $builtinType && LegacyType::BUILTIN_TYPE_OBJECT !== $type->getBuiltinType()) {
                    if ($totalProperties === $index) {
                        break 2;
                    }

                    $isNoop = true;

                    continue;
                }

                if (null === $className = $type->getClassName()) {
                    $isNoop = true;

                    continue;
                }

                if ($isResourceClass = $this->resourceClassResolver->isResourceClass($className)) {
                    $currentResourceClass = $className;
                } elseif ($totalProperties !== $index) {
                    $isNoop = true;

                    continue;
                }

                $hasAssociation = $totalProperties === $index && $isResourceClass;
                $isNoop = false;

                break;
            }

            if ($isNoop) {
                return $noop;
            }
        }

        return [$type, $hasAssociation, $currentResourceClass, $currentProperty];
    }
}
