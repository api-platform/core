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

use ApiPlatform\Core\Bridge\Doctrine\ClassMetadata\PropertyHelper;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractContextAwareFilter implements ContextAwareFilterInterface
{
    protected $logger;
    protected $propertyHelper;
    protected $properties;

    public function __construct(LoggerInterface $logger = null, PropertyHelper $propertyHelper = null, array $properties = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->propertyHelper = $propertyHelper;
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
     * {@inheritdoc}
     */
    public static function getPropertyHelperServiceName(): string
    {
        return 'api_platform.doctrine.mongodb.property_helper';
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
            return !$this->propertyHelper->isPropertyNested($property, $resourceClass);
        }

        return array_key_exists($property, $this->properties);
    }

    /**
     * Adds the necessary lookups for a nested property.
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the $field name
     *               the second element is the $associations array
     */
    protected function addLookupsForNestedProperty(string $property, Builder $aggregationBuilder, string $resourceClass): array
    {
        $propertyParts = $this->propertyHelper->splitPropertyParts($property, $resourceClass);
        $association = $propertyParts['associations'][0] ?? null;

        if (null === $association) {
            throw new InvalidArgumentException(sprintf('Cannot add lookups for property "%s" - property is not nested.', $property));
        }

        if ($this->propertyHelper->getClassMetadata($resourceClass)->hasReference($association)) {
            $aggregationBuilder->lookup($association)->alias($association);
        }

        return [$propertyParts['field'], $propertyParts['associations']];
    }
}
