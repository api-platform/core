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

namespace ApiPlatform\Doctrine\Orm\State;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

trait LinksHandlerTrait
{
    private function handleLinks(QueryBuilder $queryBuilder, array $identifiers, QueryNameGenerator $queryNameGenerator, array $context, string $resourceClass, ?string $operationName = null): void
    {
        if (!$identifiers) {
            return;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        $alias = $queryBuilder->getRootAliases()[0];

        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        $links = $operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables();

        if ($linkClass = $context['linkClass'] ?? false) {
            $newLinks = [];

            foreach ($links ?? [] as $link) {
                if ($linkClass === $link->getFromClass()) {
                    $newLinks[] = $link;
                }
            }

            $operation = $this->resourceMetadataCollectionFactory->create($linkClass)->getOperation($operationName);
            foreach ($operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables() as $link) {
                if ($resourceClass === $link->getToClass()) {
                    $newLinks[] = $link;
                }
            }

            if (!$newLinks) {
                throw new RuntimeException(sprintf('The class "%s" cannot be retrieved from "%s".', $resourceClass, $linkClass));
            }

            $links = $newLinks;
        }

        if (!$links) {
            return;
        }

        $previousAlias = $alias;
        $previousJoinProperties = $doctrineClassMetadata->getIdentifierFieldNames();
        $previousIdentifiers = $previousJoinProperties;
        $expressions = [];
        $identifiers = array_reverse($identifiers);

        foreach (array_reverse($links) as $parameterName => $link) {
            if ($link->getExpandedValue() || !$link->getFromClass()) {
                continue;
            }

            $identifierProperties = $link->getIdentifiers();
            $hasCompositeIdentifiers = 1 < \count($identifierProperties);

            if (!$link->getFromProperty() && !$link->getToProperty()) {
                $doctrineClassMetadata = $manager->getClassMetadata($link->getFromClass());
                $currentAlias = $link->getFromClass() === $resourceClass ? $alias : $queryNameGenerator->generateJoinAlias($alias);

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("$currentAlias.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $doctrineClassMetadata->getTypeOfField($identifierProperty));
                }

                $previousAlias = $currentAlias;
                $previousIdentifiers = $identifierProperties;
                $previousJoinProperties = $doctrineClassMetadata->getIdentifierFieldNames();
                continue;
            }

            $joinProperties = $doctrineClassMetadata->getIdentifierFieldNames();

            if ($link->getFromProperty() && !$link->getToProperty()) {
                $doctrineClassMetadata = $manager->getClassMetadata($link->getFromClass());
                $joinAlias = $queryNameGenerator->generateJoinAlias('m');
                $associationMapping = $doctrineClassMetadata->getAssociationMapping($link->getFromProperty()); // @phpstan-ignore-line
                $relationType = $associationMapping['type'];

                if ($relationType & ClassMetadataInfo::TO_MANY) {
                    $nextAlias = $queryNameGenerator->generateJoinAlias($alias);
                    $whereClause = [];
                    foreach ($identifierProperties as $identifierProperty) {
                        $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                        $whereClause[] = "$nextAlias.{$identifierProperty} = :$placeholder";
                        $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $doctrineClassMetadata->getTypeOfField($identifierProperty));
                    }

                    $property = $associationMapping['mappedBy'] ?? $joinProperties[0];
                    $select = isset($associationMapping['mappedBy']) ? "IDENTITY($joinAlias.$property)" : "$joinAlias.$property";
                    $expressions["$previousAlias.{$property}"] = "SELECT $select FROM {$link->getFromClass()} $nextAlias INNER JOIN $nextAlias.{$associationMapping['fieldName']} $joinAlias WHERE ".implode(' AND ', $whereClause);
                    $previousAlias = $nextAlias;
                    continue;
                }

                // A single-valued association path expression to an inverse side is not supported in DQL queries.
                if ($relationType & ClassMetadataInfo::TO_ONE && !($associationMapping['isOwningSide'] ?? true)) {
                    $queryBuilder->innerJoin("$previousAlias.".$associationMapping['mappedBy'], $joinAlias);
                } else {
                    $queryBuilder->join(
                        $link->getFromClass(),
                        $joinAlias,
                        'with',
                        "$previousAlias.{$previousJoinProperties[0]} = $joinAlias.{$associationMapping['fieldName']}"
                    );
                }

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $doctrineClassMetadata->getTypeOfField($identifierProperty));
                }

                $previousAlias = $joinAlias;
                $previousIdentifiers = $identifierProperties;
                $previousJoinProperties = $joinProperties;
                continue;
            }

            $joinAlias = $queryNameGenerator->generateJoinAlias($alias);
            $queryBuilder->join("{$previousAlias}.{$link->getToProperty()}", $joinAlias);

            foreach ($identifierProperties as $identifierProperty) {
                $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
                $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $doctrineClassMetadata->getTypeOfField($identifierProperty));
            }

            $previousAlias = $joinAlias;
            $previousIdentifiers = $identifierProperties;
            $previousJoinProperties = $joinProperties;
        }

        if ($expressions) {
            $i = 0;
            $clause = '';
            foreach ($expressions as $alias => $expression) {
                if (0 === $i) {
                    $clause .= "$alias IN (".$expression;
                    ++$i;
                    continue;
                }

                $clause .= " AND $alias IN (".$expression;
                ++$i;
            }

            $queryBuilder->andWhere($clause.str_repeat(')', $i));
        }
    }

    private function getIdentifierValue(array &$identifiers, string $name = null)
    {
        if (isset($identifiers[$name])) {
            $value = $identifiers[$name];
            unset($identifiers[$name]);

            return $value;
        }

        return array_shift($identifiers);
    }
}
