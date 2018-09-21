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

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelperTrait;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractContextAwareFilter implements ContextAwareFilterInterface
{
    use PropertyHelperTrait;

    protected $logger;
    protected $properties;

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger = null, array $properties = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger ?? new NullLogger();
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = [])
    {
        foreach ($context['filters'] as $property => $value) {
            $this->filterProperty($property, $value, $aggregationBuilder, $resourceClass, $operationName, $context);
        }
    }

    /**
     * Passes a property through the filter.
     */
    abstract protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = []);

    /**
     * Determines whether the given property is enabled.
     */
    protected function isPropertyEnabled(string $property, string $resourceClass): bool
    {
        if (null === $this->properties) {
            // to ensure sanity, nested properties must still be explicitly enabled
            return !$this->isPropertyNested($property, $resourceClass);
        }

        return array_key_exists($property, $this->properties);
    }

    /**
     * Adds the necessary lookups for a nested property.
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the $alias of the lookup,
     *               the second element is the $field name
     *               the third element is the $associations array
     */
    protected function addLookupsForNestedProperty(string $property, Builder $aggregationBuilder, string $resourceClass): array
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $association = $propertyParts['associations'][0] ?? null;

        if (null === $association) {
            throw new InvalidArgumentException(sprintf('Cannot add lookups for property "%s" - property is not nested.', $property));
        }

        $alias = $association;
        if ($this->getClassMetadata($resourceClass)->hasReference($association)) {
            $alias = "${association}_lkup";
            $aggregationBuilder->lookup($association)->alias($alias);
        }

        // assocation.property => association_lkup.property
        return [str_replace($association, $alias, $property), $propertyParts['field'], $propertyParts['associations']];
    }
}
