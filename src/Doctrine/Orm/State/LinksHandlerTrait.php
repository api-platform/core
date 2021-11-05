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

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator as ApiPlatformQueryNameGenerator;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

trait LinksHandlerTrait
{
    private function handleLinks(QueryBuilder $queryBuilder, array $identifiers, ApiPlatformQueryNameGenerator $queryNameGenerator, array $context, string $resourceClass, ?string $operationName = null): void
    {
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        $alias = $queryBuilder->getRootAliases()[0];

        if (!$identifiers) {
            return;
        }

        $links = $operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables();

        if ($linkClass = $context['linkClass'] ?? false) {
            $newLinks = [];

            foreach ($links as $link) {
                if ($linkClass === $link->getFromClass()) {
                    $newLinks[] = $link;
                }
            }

            $operation = $this->resourceMetadataCollectionFactory->create($linkClass)->getOperation($operationName);
            $links = $operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables();
            foreach ($links as $link) {
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
        $previousIdentifiers = end($links)->getIdentifiers();
        $previousJoinProperty = $doctrineClassMetadata->getIdentifier()[0];
        $expressions = [];
        $identifiers = array_reverse($identifiers);

        foreach (array_reverse($links) as $parameterName => $link) {
            if ($link->getExpandedValue() || !$link->getFromClass()) {
                continue;
            }

            $identifierProperties = $link->getIdentifiers();

            if (!$link->getFromProperty() && !$link->getToProperty()) {
                $doctrineClassMetadata = $manager->getClassMetadata($link->getFromClass());
                $currentAlias = $link->getFromClass() === $resourceClass ? $alias : $queryNameGenerator->generateJoinAlias($alias);

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("{$currentAlias}.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, array_shift($identifiers), $doctrineClassMetadata->getTypeOfField($identifierProperty));
                }

                $previousAlias = $currentAlias;
                $previousIdentifiers = $identifierProperties;
                $previousJoinProperty = $doctrineClassMetadata->getIdentifier()[0];
                continue;
            }

            if (1 < \count($previousIdentifiers) || 1 < \count($identifierProperties)) {
                throw new RuntimeException('Multiple identifiers on a relation can not be handled automatically, implement your own query.');
            }

            $previousIdentifier = $previousIdentifiers[0];
            $identifierProperty = $identifierProperties[0];
            $joinProperty = $doctrineClassMetadata->getIdentifier()[0];
            $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);

            if ($link->getFromProperty() && !$link->getToProperty()) {
                $doctrineClassMetadata = $manager->getClassMetadata($link->getFromClass());
                $joinAlias = $queryNameGenerator->generateJoinAlias('m');
                $associationMapping = $doctrineClassMetadata->getAssociationMapping($link->getFromProperty());
                $relationType = $associationMapping['type'];

                if ($relationType & ClassMetadataInfo::TO_MANY) {
                    $nextAlias = $queryNameGenerator->generateJoinAlias($alias);
                    $expressions["$previousAlias.$previousIdentifier"] = "SELECT $joinAlias.{$previousIdentifier} FROM {$link->getFromClass()} $nextAlias INNER JOIN $nextAlias.{$link->getFromProperty()} $joinAlias WHERE $nextAlias.{$identifierProperty} = :$placeholder";
                    $queryBuilder->setParameter($placeholder, array_shift($identifiers), $doctrineClassMetadata->getTypeOfField($identifierProperty));
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
                        "{$previousAlias}.{$previousJoinProperty} = $joinAlias.{$link->getFromProperty()}"
                    );
                }

                $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
                $queryBuilder->setParameter($placeholder, array_shift($identifiers), $doctrineClassMetadata->getTypeOfField($identifierProperty));
                $previousAlias = $joinAlias;
                $previousIdentifier = $identifierProperty;
                $previousJoinProperty = $joinProperty;
                continue;
            }

            $joinAlias = $queryNameGenerator->generateJoinAlias($alias);
            $queryBuilder->join("{$previousAlias}.{$link->getToProperty()}", $joinAlias);
            $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
            $queryBuilder->setParameter($placeholder, array_shift($identifiers), $doctrineClassMetadata->getTypeOfField($identifierProperty));
            $previousAlias = $joinAlias;
            $previousIdentifier = $identifierProperty;
            $previousJoinProperty = $joinProperty;
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
