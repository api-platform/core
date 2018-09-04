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

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\PropertyHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\RequestParser;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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
    protected $requestStack;
    protected $logger;
    protected $propertyHelper;
    protected $properties;

    /**
     * @param ManagerRegistry|null $managerRegistry No prefix to prevent autowiring of this deprecated property
     * @param RequestStack|null    $requestStack    No prefix to prevent autowiring of this deprecated property
     */
    public function __construct($managerRegistry = null, $requestStack = null, LoggerInterface $logger = null, PropertyHelper $propertyHelper = null, array $properties = null)
    {
        if (null !== $managerRegistry) {
            @trigger_error(sprintf('Passing an instance of "%s" is deprecated since 2.4.', ManagerRegistry::class), E_USER_DEPRECATED);
        }
        if (null !== $requestStack) {
            @trigger_error(sprintf('Passing an instance of "%s" is deprecated since 2.2. Use "filters" context key instead.', RequestStack::class), E_USER_DEPRECATED);
        }
        if (null === $propertyHelper) {
            @trigger_error(sprintf('Not injecting "%s" is deprecated since API Platform 2.4 and will not be possible anymore in API Platform 3', PropertyHelper::class), E_USER_DEPRECATED);
            $propertyHelper = new PropertyHelper($managerRegistry);
        }

        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->logger = $logger ?? new NullLogger();
        $this->propertyHelper = $propertyHelper;
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/*, array $context = []*/)
    {
        @trigger_error(sprintf('Using "%s::apply()" is deprecated since 2.2. Use "%s::apply()" with the "filters" context key instead.', __CLASS__, AbstractContextAwareFilter::class), E_USER_DEPRECATED);

        if (null === $this->requestStack || null === $request = $this->requestStack->getCurrentRequest()) {
            return;
        }

        foreach ($this->extractProperties($request, $resourceClass) as $property => $value) {
            $this->filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);
        }
    }

    /**
     * Passes a property through the filter.
     */
    abstract protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null/*, array $context = []*/);

    /**
     * Determines whether the given property is enabled.
     */
    protected function isPropertyEnabled(string $property/*, string $resourceClass*/): bool
    {
        if (\func_num_args() > 1) {
            $resourceClass = func_get_arg(1);
        } else {
            if (__CLASS__ !== \get_class($this)) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a second `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
                }
            }
            $resourceClass = null;
        }

        if (null === $this->properties) {
            // to ensure sanity, nested properties must still be explicitly enabled
            return !$this->propertyHelper->isPropertyNested($property, $resourceClass);
        }

        return array_key_exists($property, $this->properties);
    }

    /**
     * Extracts properties to filter from the request.
     */
    protected function extractProperties(Request $request/*, string $resourceClass*/): array
    {
        @trigger_error(sprintf('The use of "%s::extractProperties()" is deprecated since 2.2. Use the "filters" key of the context instead.', __CLASS__), E_USER_DEPRECATED);

        $resourceClass = \func_num_args() > 1 ? (string) func_get_arg(1) : null;
        $needsFixing = false;
        if (null !== $this->properties) {
            foreach ($this->properties as $property => $value) {
                if (($this->propertyHelper->isPropertyNested($property, $resourceClass) || $this->propertyHelper->isPropertyEmbedded($property, $resourceClass)) && $request->query->has(str_replace('.', '_', $property))) {
                    $needsFixing = true;
                }
            }
        }

        if ($needsFixing) {
            $request = RequestParser::parseAndDuplicateRequest($request);
        }

        return $request->query->all();
    }

    /**
     * Adds the necessary joins for a nested property.
     *
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the join $alias of the leaf entity,
     *               the second element is the $field name
     *               the third element is the $associations array
     */
    protected function addJoinsForNestedProperty(string $property, string $rootAlias, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator/*, string $resourceClass, string $joinType*/): array
    {
        if (\func_num_args() > 4) {
            $resourceClass = func_get_arg(4);
        } else {
            if (__CLASS__ !== \get_class($this)) {
                $r = new \ReflectionMethod($this, __FUNCTION__);
                if (__CLASS__ !== $r->getDeclaringClass()->getName()) {
                    @trigger_error(sprintf('Method %s() will have a fifth `$resourceClass` argument in version API Platform 3.0. Not defining it is deprecated since API Platform 2.1.', __FUNCTION__), E_USER_DEPRECATED);
                }
            }
            $resourceClass = null;
        }

        if (\func_num_args() > 5) {
            $joinType = func_get_arg(5);
        } else {
            $joinType = null;
        }

        $propertyParts = $this->propertyHelper->splitPropertyParts($property, $resourceClass);
        $parentAlias = $rootAlias;
        $alias = null;

        foreach ($propertyParts['associations'] as $association) {
            $alias = QueryBuilderHelper::addJoinOnce($queryBuilder, $queryNameGenerator, $parentAlias, $association, $joinType);
            $parentAlias = $alias;
        }

        if (null === $alias) {
            throw new InvalidArgumentException(sprintf('Cannot add joins for property "%s" - property is not nested.', $property));
        }

        return [$alias, $propertyParts['field'], $propertyParts['associations']];
    }

    /**
     * Gets class metadata for the given resource.
     */
    protected function getClassMetadata(string $resourceClass): ClassMetadata
    {
        @trigger_error(sprintf('Using "%s::getClassMetadata()" is deprecated since 2.4. Use "%s::getClassMetadata()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->getClassMetadata($resourceClass);
    }

    /**
     * Determines whether the given property is mapped.
     */
    protected function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool
    {
        @trigger_error(sprintf('Using "%s::isPropertyMapped()" is deprecated since 2.4. Use "%s::isPropertyMapped()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->isPropertyMapped($property, $resourceClass, $allowAssociation);
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

        @trigger_error(sprintf('Using "%s::isPropertyNested()" is deprecated since 2.4. Use "%s::isPropertyNested()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->isPropertyNested($property, $resourceClass);
    }

    /**
     * Determines whether the given property is embedded.
     */
    protected function isPropertyEmbedded(string $property, string $resourceClass): bool
    {
        @trigger_error(sprintf('Using "%s::isPropertyEmbedded()" is deprecated since 2.4. Use "%s::isPropertyEmbedded()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->isPropertyEmbedded($property, $resourceClass);
    }

    /**
     * Gets nested class metadata for the given resource.
     *
     * @param string[] $associations
     */
    protected function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata
    {
        @trigger_error(sprintf('Using "%s::getNestedMetadata()" is deprecated since 2.4. Use "%s::getNestedMetadata()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->getNestedMetadata($resourceClass, $associations);
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

        @trigger_error(sprintf('Using "%s::splitPropertyParts()" is deprecated since 2.4. Use "%s::splitPropertyParts()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->splitPropertyParts($property, $resourceClass);
    }

    /**
     * Gets the Doctrine Type of a given property/resourceClass.
     *
     * @return Type|string|null
     */
    protected function getDoctrineFieldType(string $property, string $resourceClass)
    {
        @trigger_error(sprintf('Using "%s::getDoctrineFieldType()" is deprecated since 2.4. Use "%s::getDoctrineFieldType()" instead.', __CLASS__, PropertyHelper::class), E_USER_DEPRECATED);

        return $this->propertyHelper->getDoctrineFieldType($property, $resourceClass);
    }
}
