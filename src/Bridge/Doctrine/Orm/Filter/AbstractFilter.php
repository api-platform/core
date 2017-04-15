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

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Util\RequestParser;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
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
    protected $properties;

    public function __construct(ManagerRegistry $managerRegistry, RequestStack $requestStack, LoggerInterface $logger = null, array $properties = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->requestStack = $requestStack;
        $this->logger = $logger ?? new NullLogger();
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        foreach ($this->extractProperties($request) as $property => $value) {
            $this->filterProperty($property, $value, $queryBuilder, $queryNameGenerator, $resourceClass, $operationName);
        }
    }

    /**
     * Passes a property through the filter.
     *
     * @param string                      $property
     * @param mixed                       $value
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $resourceClass
     * @param string|null                 $operationName
     */
    abstract protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null);

    /**
     * Gets class metadata for the given resource.
     *
     * @param string $resourceClass
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata(string $resourceClass): ClassMetadata
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
    protected function isPropertyEnabled(string $property): bool
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
    protected function isPropertyMapped(string $property, string $resourceClass, bool $allowAssociation = false): bool
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
    protected function isPropertyNested(string $property): bool
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
    protected function getNestedMetadata(string $resourceClass, array $associations): ClassMetadata
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
    protected function splitPropertyParts(string $property): array
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
    protected function extractProperties(Request $request): array
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

    /**
     * Adds the necessary joins for a nested property.
     *
     * @param string                      $property
     * @param string                      $rootAlias
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the join $alias of the leaf entity,
     *               the second element is the $field name
     *               the third element is the $associations array
     */
    protected function addJoinsForNestedProperty(string $property, string $rootAlias, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator): array
    {
        $propertyParts = $this->splitPropertyParts($property);
        $parentAlias = $rootAlias;

        foreach ($propertyParts['associations'] as $association) {
            $alias = $this->addJoinOnce($queryBuilder, $queryNameGenerator, $parentAlias, $association);
            $parentAlias = $alias;
        }

        if (!isset($alias)) {
            throw new InvalidArgumentException(sprintf('Cannot add joins for property "%s" - property is not nested.', $property));
        }

        return [$alias, $propertyParts['field'], $propertyParts['associations']];
    }

    /**
     * Get the existing join from queryBuilder DQL parts.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $alias
     * @param string       $association  the association field
     *
     * @return Join|null
     */
    private function getExistingJoin(QueryBuilder $queryBuilder, string $alias, string $association)
    {
        $parts = $queryBuilder->getDQLPart('join');

        if (!isset($parts['o'])) {
            return;
        }

        foreach ($parts['o'] as $join) {
            if (sprintf('%s.%s', $alias, $association) === $join->getJoin()) {
                return $join;
            }
        }
    }

    /**
     * Adds a join to the queryBuilder if none exists.
     *
     * @param QueryBuilder                $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string                      $alias
     * @param string                      $association        the association field
     *
     * @return string the new association alias
     */
    protected function addJoinOnce(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $alias, string $association): string
    {
        $join = $this->getExistingJoin($queryBuilder, $alias, $association);

        if (null === $join) {
            $associationAlias = $queryNameGenerator->generateJoinAlias($association);
            $queryBuilder
                ->join(sprintf('%s.%s', $alias, $association), $associationAlias);
        } else {
            $associationAlias = $join->getAlias();
        }

        return $associationAlias;
    }
}
