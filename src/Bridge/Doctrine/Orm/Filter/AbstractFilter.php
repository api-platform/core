<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Util\RequestParser;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * Abstract class with helpers for easing the implementation of a filter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
abstract class AbstractFilter implements FilterInterface
{
    protected $managerRegistry;
    protected $properties;

    public function __construct(ManagerRegistry $managerRegistry, array $properties = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->properties = $properties;
    }

    /**
     * Gets class metadata for the given resource.
     *
     * @param string $resourceClass
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata(string $resourceClass) : ClassMetadata
    {
        return $this
            ->managerRegistry
            ->getManagerForClass($resourceClass)
            ->getClassMetadata($resourceClass);
    }

    /**
     * Determines whether the given property is enabled.
     *
     * @param string $property
     *
     * @return bool
     */
    protected function isPropertyEnabled(string $property) : bool
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
     * @param string $property
     * @param string $resourceClass
     * @param bool   $allowAssociation
     *
     * @return bool
     */
    protected function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false) : bool
    {
        if ($this->isPropertyNested($property)) {
            $propertyParts = $this->splitPropertyParts($property);
            $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            $property = $propertyParts['field'];
        } else {
            $metadata = $this->getClassMetadata($resourceClass);
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
    protected function isPropertyNested(string $property) : bool
    {
        return false !== strpos($property, '.');
    }

    /**
     * Gets nested class metadata for the given resource.
     *
     * @param string   $resourceClass
     * @param string[] $associations
     *
     * @return ClassMetadata
     */
    protected function getNestedMetadata(string $resourceClass, array $associations) : ClassMetadata
    {
        $metadata = $this->getClassMetadata($resourceClass);

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
    protected function splitPropertyParts(string $property) : array
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
    protected function extractProperties(Request $request) : array
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
