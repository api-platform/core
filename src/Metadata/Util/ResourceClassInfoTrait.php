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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Retrieves information about a resource class.
 *
 * @internal
 */
trait ResourceClassInfoTrait
{
    use ClassInfoTrait;

    private ?ResourceClassResolverInterface $resourceClassResolver = null;
    private ?ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null;

    /**
     * Gets the resource class of the given object.
     *
     * @param bool $strict If true, object class is expected to be a resource class
     *
     * @return string|null The resource class, or null if object class is not a resource class
     */
    private function getResourceClass(object $object, bool $strict = false): ?string
    {
        $objectClass = $this->getObjectClass($object);

        if (null === $this->resourceClassResolver) {
            return $objectClass;
        }

        if (!$strict && !$this->resourceClassResolver->isResourceClass($objectClass)) {
            return null;
        }

        return $this->resourceClassResolver->getResourceClass($object);
    }

    private function isResourceClass(string $class): bool
    {
        if ($this->resourceClassResolver instanceof ResourceClassResolverInterface) {
            return $this->resourceClassResolver->isResourceClass($class);
        }

        if ($this->resourceMetadataFactory) {
            return \count($this->resourceMetadataFactory->create($class)) > 0;
        }

        return false;
    }

    private function getTypeFromProperty(ApiProperty $propertyMetadata): ?Type
    {
        return $propertyMetadata->getNativeType();
    }

    private function extractClassNameFromType(Type $type): ?string
    {
        return TypeHelper::getClassName(TypeHelper::getCollectionValueType($type) ?? $type);
    }

    /**
     * Gets the class name referenced by a property's type, if any.
     *
     * Returns the underlying class for object types (including the inner type of collections);
     * returns null for scalar/builtin types or untyped properties. The returned class is not
     * required to be an API resource — callers that need that constraint must check explicitly.
     */
    protected function getClassNameFromProperty(ApiProperty $propertyMetadata): ?string
    {
        if (!($type = $this->getTypeFromProperty($propertyMetadata))) {
            return null;
        }

        return $this->extractClassNameFromType($type);
    }
}
