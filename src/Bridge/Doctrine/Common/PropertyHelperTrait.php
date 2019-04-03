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

namespace ApiPlatform\Core\Bridge\Doctrine\Common;

use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadataFactoryOptionsTrait;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/**
 * Helper trait for getting information regarding a property using the resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
trait PropertyHelperTrait
{
    use PropertyMetadataFactoryOptionsTrait;

    /** @var PropertyMetadataFactoryInterface|null */
    protected $propertyMetadataFactory;

    abstract protected function getManagerRegistry(): ManagerRegistry;

    /**
     * Determines whether the given property is enabled.
     */
    protected function isPropertyEnabled(string $property/*, string $resourceClass, array $context = []*/): bool
    {
        if (\func_num_args() < 3 && __CLASS__ !== \get_class($this) && __CLASS__ !== (new \ReflectionMethod($this, __FUNCTION__))->getDeclaringClass()->getName()) {
            if (\func_num_args() < 2) {
                @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
            }

            @trigger_error(sprintf('Method %s() will have a third "$context" argument in API Platform 3.0. Not defining it is deprecated since API Platform 2.4.', __FUNCTION__), E_USER_DEPRECATED);
        }

        $resourceClass = 1 < \func_num_args() ? (string) func_get_arg(1) : null;
        $context = 2 < \func_num_args() ? (array) func_get_arg(2) : [];

        if (null !== $this->properties) {
            return \array_key_exists($property, $this->properties);
        }

        if (null !== $resourceClass && null !== $this->propertyMetadataFactory) {
            try {
                $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property, $this->getPropertyMetadataFactoryOptions($context));
            } catch (PropertyNotFoundException $e) {
                return false;
            }

            // to ensure sanity, unreadable properties must still be explicitly enabled
            if (!$propertyMetadata->isReadable()) {
                return false;
            }
        }

        // to ensure sanity, nested properties must still be explicitly enabled
        if (null === $resourceClass) {
            return !$this->isPropertyNested($property);
        }

        return !$this->isPropertyNested($property, $resourceClass);
    }

    /**
     * Determines whether the given property is mapped.
     */
    protected function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool
    {
        if ($this->isPropertyNested($property, $resourceClass)) {
            $propertyParts = $this->splitPropertyParts($property, $resourceClass);
            $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            $property = $propertyParts['field'];
        } else {
            $metadata = $this->getClassMetadata($resourceClass);
        }

        return $metadata->hasField($property) || ($allowAssociation && $metadata->hasAssociation($property));
    }

    /**
     * Determines whether the given property is nested.
     */
    protected function isPropertyNested(string $property/*, string $resourceClass*/): bool
    {
        if (\func_num_args() > 1) {
            $resourceClass = (string) func_get_arg(1);
        } else {
            if (__CLASS__ !== \get_class($this)) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
                }
            }
            $resourceClass = null;
        }

        $pos = strpos($property, '.');
        if (false === $pos) {
            return false;
        }

        return null !== $resourceClass && $this->getClassMetadata($resourceClass)->hasAssociation(substr($property, 0, $pos));
    }

    /**
     * Determines whether the given property is embedded.
     */
    protected function isPropertyEmbedded(string $property, string $resourceClass): bool
    {
        return false !== strpos($property, '.') && $this->getClassMetadata($resourceClass)->hasField($property);
    }

    /**
     * Splits the given property into parts.
     *
     * Returns an array with the following keys:
     *   - associations: array of associations according to nesting order
     *   - field: string holding the actual field (leaf node)
     */
    protected function splitPropertyParts(string $property/*, string $resourceClass*/): array
    {
        $resourceClass = null;
        $parts = explode('.', $property);

        if (\func_num_args() > 1) {
            $resourceClass = func_get_arg(1);
        } elseif (__CLASS__ !== \get_class($this)) {
            $r = new \ReflectionMethod($this, __FUNCTION__);
            if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
            }
        }

        if (null === $resourceClass) {
            return [
                'associations' => \array_slice($parts, 0, -1),
                'field' => end($parts),
            ];
        }

        $metadata = $this->getClassMetadata($resourceClass);
        $slice = 0;

        foreach ($parts as $part) {
            if ($metadata->hasAssociation($part)) {
                $metadata = $this->getClassMetadata($metadata->getAssociationTargetClass($part));
                ++$slice;
            }
        }

        if (\count($parts) === $slice) {
            --$slice;
        }

        return [
            'associations' => \array_slice($parts, 0, $slice),
            'field' => implode('.', \array_slice($parts, $slice)),
        ];
    }

    /**
     * Gets the Doctrine Type of a given property/resourceClass.
     *
     * @return Type|string|null
     */
    protected function getDoctrineFieldType(string $property, string $resourceClass)
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        return $metadata->getTypeOfField($propertyParts['field']);
    }

    /**
     * Gets nested class metadata for the given resource.
     *
     * @param string[] $associations
     */
    protected function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata
    {
        $metadata = $this->getClassMetadata($resourceClass);

        foreach ($associations as $association) {
            if ($metadata->hasAssociation($association)) {
                $associationClass = $metadata->getAssociationTargetClass($association);

                $metadata = $this->getClassMetadata($associationClass);
            }
        }

        return $metadata;
    }

    /**
     * Gets class metadata for the given resource.
     */
    protected function getClassMetadata(string $resourceClass): ClassMetadata
    {
        return $this
            ->getManagerRegistry()
            ->getManagerForClass($resourceClass)
            ->getClassMetadata($resourceClass);
    }
}
