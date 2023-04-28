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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

trait LinksHandlerTrait
{
    use CommonLinksHandlerTrait;

    private function handleLinks(QueryBuilder $queryBuilder, array $identifiers, QueryNameGenerator $queryNameGenerator, array $context, string $entityClass, Operation $operation): void
    {
        if (!$identifiers) {
            return;
        }

        $manager = $this->managerRegistry->getManagerForClass($entityClass);
        $doctrineClassMetadata = $manager->getClassMetadata($entityClass);
        $alias = $queryBuilder->getRootAliases()[0];

        $links = $this->getLinks($entityClass, $operation, $context);

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

            $fromClass = $link->getFromClass();
            if (!$this->managerRegistry->getManagerForClass($fromClass)) {
                $fromClass = $this->getLinkFromClass($link, $operation);
            }

            $fromClassMetadata = $manager->getClassMetadata($fromClass);
            $identifierProperties = $link->getIdentifiers();
            $hasCompositeIdentifiers = 1 < \count($identifierProperties);

            if (!$link->getFromProperty() && !$link->getToProperty()) {
                $currentAlias = $fromClass === $entityClass ? $alias : $queryNameGenerator->generateJoinAlias($alias);

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("$currentAlias.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $fromClassMetadata->getTypeOfField($identifierProperty));
                }

                $previousAlias = $currentAlias;
                $previousJoinProperties = $fromClassMetadata->getIdentifierFieldNames();
                continue;
            }

            $joinProperties = $doctrineClassMetadata->getIdentifierFieldNames();

            if ($link->getFromProperty() && !$link->getToProperty()) {
                $joinAlias = $queryNameGenerator->generateJoinAlias('m');
                $associationMapping = $fromClassMetadata->getAssociationMapping($link->getFromProperty()); // @phpstan-ignore-line
                $relationType = $associationMapping['type'];

                if ($relationType & ClassMetadataInfo::TO_MANY) {
                    $nextAlias = $queryNameGenerator->generateJoinAlias($alias);
                    $whereClause = [];
                    foreach ($identifierProperties as $identifierProperty) {
                        $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                        $whereClause[] = "$nextAlias.{$identifierProperty} = :$placeholder";
                        $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $fromClassMetadata->getTypeOfField($identifierProperty));
                    }

                    $property = $associationMapping['mappedBy'] ?? $joinProperties[0];
                    $select = isset($associationMapping['mappedBy']) ? "IDENTITY($joinAlias.$property)" : "$joinAlias.$property";
                    $expressions["$previousAlias.{$property}"] = "SELECT $select FROM {$fromClass} $nextAlias INNER JOIN $nextAlias.{$associationMapping['fieldName']} $joinAlias WHERE ".implode(' AND ', $whereClause);
                    $previousAlias = $nextAlias;
                    continue;
                }

                // A single-valued association path expression to an inverse side is not supported in DQL queries.
                if ($relationType & ClassMetadataInfo::TO_ONE && !($associationMapping['isOwningSide'] ?? true)) {
                    $queryBuilder->innerJoin("$previousAlias.".$associationMapping['mappedBy'], $joinAlias);
                } else {
                    $queryBuilder->join(
                        $fromClass,
                        $joinAlias,
                        'WITH',
                        "$previousAlias.{$previousJoinProperties[0]} = $joinAlias.{$associationMapping['fieldName']}"
                    );
                }

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $fromClassMetadata->getTypeOfField($identifierProperty));
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
                $queryBuilder->setParameter($placeholder, $this->getIdentifierValue($identifiers, $hasCompositeIdentifiers ? $identifierProperty : null), $fromClassMetadata->getTypeOfField($identifierProperty));
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

    private function getLinkFromClass(Link $link, Operation $operation): string
    {
        $fromClass = $link->getFromClass();
        if ($fromClass === $operation->getClass() && $entityClass = $this->getStateOptionsEntityClass($operation)) {
            return $entityClass;
        }

        $operation = $this->resourceMetadataCollectionFactory->create($fromClass)->getOperation();

        if ($entityClass = $this->getStateOptionsEntityClass($operation)) {
            return $entityClass;
        }

        throw new \Exception('Can not found a doctrine class for this link.');
    }

    private function getStateOptionsEntityClass(Operation $operation): ?string
    {
        if (($options = $operation->getStateOptions()) && $options instanceof Options && $entityClass = $options->getEntityClass()) {
            return $entityClass;
        }

        return null;
    }
}
