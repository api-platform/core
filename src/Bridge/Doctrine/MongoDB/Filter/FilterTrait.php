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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Filter;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

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
