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

use ApiPlatform\Doctrine\Common\State\LinksHandlerTrait as CommonLinksHandlerTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

trait LinksHandlerTrait
{
    use CommonLinksHandlerTrait;

    private function handleLinks(QueryBuilder $queryBuilder, array $identifiers, QueryNameGenerator $queryNameGenerator, array $context, string $resourceClass, Operation $operation): void
    {
        if (!$identifiers) {
            return;
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        $alias = $queryBuilder->getRootAliases()[0];

        $links = $this->getLinks($resourceClass, $operation, $context);

        if (!$links) {
            return;
        }

        $previousAlias = $alias;
        $previousJoinProperties = $doctrineClassMetadata->getIdentifierFieldNames();
        $expressions = [];
        $identifiers = array_reverse($identifiers);

        foreach (array_reverse($links) as $link) {
            if (null !== $link->getExpandedValue() || !$link->getFromClass()) {
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
                        'WITH',
                        "$previousAlias.{$previousJoinProperties[0]} = $joinAlias.{$associationMapping['fieldName']}"
                    );
                }

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $doctrineClassMetadata->getTypeOfField($identifierProperty));
                }

                $previousAlias = $joinAlias;
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
}
