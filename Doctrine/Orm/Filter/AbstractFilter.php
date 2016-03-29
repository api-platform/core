<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Util\RequestParser;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    /**
     * @var array|null
     */
    protected $properties;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry, array $properties = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->properties = $properties;
    }

    /**
     * Gets class metadata for the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata(ResourceInterface $resource)
    {
        $entityClass = $resource->getEntityClass();

        return $this
            ->managerRegistry
            ->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass);
    }

    /**
     * Determines whether the given property is enabled.
     *
     * @param string $property
     *
     * @return bool
     */
    protected function isPropertyEnabled($property)
    {
        if (null === $this->properties) {
            // to ensure sanity, nested properties must still be explicitly enabled
            return !$this->isPropertyNested($property);
        }

        return array_key_exists($property, $this->properties);
    }

    /**
     * Determines whether the given property is mapped.
     *
     * @param string            $property
     * @param ResourceInterface $resource
     * @param bool              $allowAssociation
     *
     * @return bool
     */
    protected function isPropertyMapped($property, ResourceInterface $resource, $allowAssociation = false)
    {
        if ($this->isPropertyNested($property)) {
            $propertyParts = $this->splitPropertyParts($property);
            $metadata = $this->getNestedMetadata($resource, $propertyParts['associations']);
            $property = $propertyParts['field'];
        } else {
            $metadata = $this->getClassMetadata($resource);
        }

        return $metadata->hasField($property) || ($allowAssociation && $metadata->hasAssociation($property));
    }

    /**
     * Determines whether the given property is nested.
     *
     * @param string $property
     *
     * @return bool
     */
    protected function isPropertyNested($property)
    {
        return false !== strpos($property, '.');
    }

    /**
     * Gets nested class metadata for the given resource.
     *
     * @param ResourceInterface $resource
     * @param string[]          $associations
     *
     * @return ClassMetadata
     */
    protected function getNestedMetadata(ResourceInterface $resource, array $associations)
    {
        $metadata = $this->getClassMetadata($resource);

        foreach ($associations as $association) {
            if ($metadata->hasAssociation($association)) {
                $associationClass = $metadata->getAssociationTargetClass($association);

                $metadata = $this
                    ->managerRegistry
                    ->getManagerForClass($associationClass)
                    ->getClassMetadata($associationClass);
            }
        }

        return $metadata;
    }

    /**
     * Splits the given property into parts.
     *
     * Returns an array with the following keys:
     *   - associations: array of associations according to nesting order
     *   - field: string holding the actual field (leaf node)
     *
     * @param string $property
     *
     * @return array
     */
    protected function splitPropertyParts($property)
    {
        $parts = explode('.', $property);

        return [
            'associations' => array_slice($parts, 0, -1),
            'field' => end($parts),
        ];
    }

    /**
     * Extracts properties to filter from the request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function extractProperties(Request $request)
    {
        $needsFixing = false;

        if (null !== $this->properties) {
            foreach ($this->properties as $property => $value) {
                if ($this->isPropertyNested($property) && $request->query->has(str_replace('.', '_', $property))) {
                    $needsFixing = true;
                }
            }
        }

        if ($needsFixing) {
            $request = RequestParser::parseAndDuplicateRequest($request);
        }

        return $request->query->all();
    }
}
