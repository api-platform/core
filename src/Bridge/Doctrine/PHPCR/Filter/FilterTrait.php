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

namespace ApiPlatform\Core\Bridge\Doctrine\PHPCR\Filter;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata as PHPCRClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder as Builder;

/**
 * {@inheritdoc}
 *
 * Trait with helpers for easing the implementation of a filter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
trait FilterTrait
{
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
        } else {
            if (__CLASS__ !== \get_class($this)) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
                }
            }
        }

        if (null === $resourceClass) {
            return [
                'associations' => \array_slice($parts, 0, -1),
                'field' => end($parts),
            ];
        }

        /** @var PHPCRClassMetadata $metadata */
        $metadata = $this->getClassMetadata($resourceClass);
        $slice = 0;

        foreach ($parts as $part) {
            if (isset($metadata->childMappings[$part])) {
                $metadata = $this->getClassMetadata($metadata->reflFields[$part]->class);
                ++$slice;
            }  elseif ($metadata->hasAssociation($part)) {
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
     * Gets nested class metadata for the given resource.
     *
     * @param string[] $associations
     */
    protected function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata
    {
        /** @var PHPCRClassMetadata $metadata */
        $metadata = $this->getClassMetadata($resourceClass);

        foreach ($associations as $association) {
            if (isset($metadata->childMappings[$association])) {
                $associationClass =$metadata->reflFields[$association]->class;
                $metadata = $this->getClassMetadata($associationClass);
            }  elseif ($metadata->hasAssociation($association)) {
                $associationClass = $metadata->getAssociationTargetClass($association);

                $metadata = $this->getClassMetadata($associationClass);
            }
        }

        return $metadata;
    }

    /**
     * Adds the necessary lookups for a nested property.
     *
     * @throws InvalidArgumentException If property is not nested
     */
    protected function addLookupsForNestedProperty(string $property, Builder $aggregationBuilder, string $resourceClass): void
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $association = $propertyParts['associations'][0] ?? null;

        if (null === $association) {
            throw new InvalidArgumentException(sprintf('Cannot add lookups for property "%s" - property is not nested.', $property));
        }

        if ($this->getClassMetadata($resourceClass)->hasReference($association)) {
            $aggregationBuilder->lookup($association)->alias($association);
        }
    }
}
