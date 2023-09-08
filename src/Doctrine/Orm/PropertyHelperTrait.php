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

namespace ApiPlatform\Doctrine\Orm;

use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use Doctrine\ORM\Mapping\ClassMetadata as ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;

/**
 * Helper trait regarding a property in an entity using the resource metadata.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
trait PropertyHelperTrait
{
    abstract protected function getManagerRegistry(): ManagerRegistry;

    /**
     * Splits the given property into parts.
     */
    abstract protected function splitPropertyParts(string $property, string $resourceClass): array;

    /**
     * Gets class metadata for the given resource.
     */
    protected function getClassMetadata(string $resourceClass): ClassMetadata
    {
        $manager = $this
            ->getManagerRegistry()
            ->getManagerForClass($resourceClass);

        if ($manager) {
            return $manager->getClassMetadata($resourceClass);
        }

        return new ClassMetadataInfo($resourceClass);
    }

    /**
     * Adds the necessary joins for a nested property.
     *
     * @throws InvalidArgumentException If property is not nested
     *
     * @return array An array where the first element is the join $alias of the leaf entity,
     *               the second element is the $field name
     *               the third element is the $associations array
     */
    protected function addJoinsForNestedProperty(string $property, string $rootAlias, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $joinType): array
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
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
}
