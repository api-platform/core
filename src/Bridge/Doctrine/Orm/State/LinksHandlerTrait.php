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

namespace ApiPlatform\Bridge\Doctrine\Orm\State;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProduct;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;

trait LinksHandlerTrait
{
    private function handleLinks(QueryBuilder $queryBuilder, array $identifiers, QueryNameGenerator $queryNameGenerator, array $context, string $resourceClass, ?string $operationName = null): void
    {
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        $doctrineClassMetadata = $manager->getClassMetadata($resourceClass);
        $alias = $queryBuilder->getRootAliases()[0];

        $links = $operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables();

        // if ($linkClass = $context['linkClass'] ?? false) {
        //     foreach ($links as $link) {
        //         if ($linkClass === $link->getTargetClass()) {
        //             foreach ($identifiers as $identifier => $value) {
        //                 $this->applyLink($queryBuilder, $queryNameGenerator, $doctrineClassMetadata, $alias, $link, $identifier, $value);
        //             }
        //
        //             return;
        //         }
        //     }
        // }

        if (!$links) {
            return;
        }

        $previousAlias = $alias;
        $previousIdentifiers = end($links)->getIdentifiers();
        $expressions = [];
        $i = 0;

        foreach (array_reverse($links) as $parameterName => $link) {
            if ($link->getExpandedValue() || !$link->getFromClass()) {
                ++$i;
                continue;
            }

            $identifierProperties = $link->getIdentifiers();
            $currentAlias = $i === 0 ? $alias : $queryNameGenerator->generateJoinAlias($alias);

            if (!$link->getFromProperty() && !$link->getToProperty()) {
                $doctrineClassMetadata = $manager->getClassMetadata($link->getFromClass());

                foreach ($identifierProperties as $identifierProperty) {
                    $placeholder = $queryNameGenerator->generateParameterName($identifierProperty);
                    $queryBuilder->andWhere("{$currentAlias}.$identifierProperty = :$placeholder");
                    $queryBuilder->setParameter($placeholder, $identifiers[$identifierProperty], $doctrineClassMetadata->getTypeOfField($identifierProperty));
                }

                $previousAlias = $currentAlias;
                $previousIdentifiers = $identifierProperties;
                ++$i;
                continue;
            }

            if (1 < \count($previousIdentifiers)) {
                throw new RuntimeException('Composite identifiers on a relation can not be handled automatically, implement your own query.');
            }

            $previousIdentifier = $previousIdentifiers[0];

            if ($link->getFromProperty()) {
                $doctrineClassMetadata = $manager->getClassMetadata($link->getFromClass());
                $joinAlias = $queryNameGenerator->generateJoinAlias('m');
                $assocationMapping = $doctrineClassMetadata->getAssociationMappings()[$link->getFromProperty()];
                $relationType = $assocationMapping['type'];

                if ($relationType & ClassMetadataInfo::TO_MANY) {
                    $nextAlias = $queryNameGenerator->generateJoinAlias($alias);
                    $expressions["$previousAlias.$previousIdentifier"] = "SELECT $joinAlias.{$previousIdentifier} FROM {$link->getFromClass()} $nextAlias INNER JOIN $nextAlias.{$link->getFromProperty()} $joinAlias WHERE $nextAlias.{$identifierProperty} = :$placeholder";
                    $queryBuilder->setParameter($placeholder, $identifiers[$previousIdentifier], $doctrineClassMetadata->getTypeOfField($identifierProperty));
                    $previousAlias = $nextAlias;
                    ++$i;
                    continue;
                }


                // A single-valued association path expression to an inverse side is not supported in DQL queries.
                if ($relationType & ClassMetadataInfo::TO_ONE && !$assocationMapping['isOwningSide']) {
                    $queryBuilder->innerJoin("$previousAlias.".$assocationMapping['mappedBy'], $joinAlias);
                } else {
                    $queryBuilder->join(
                        $link->getFromClass(),
                        $joinAlias,
                        'with',
                        "{$previousAlias}.{$previousIdentifier} = $joinAlias.{$link->getFromProperty()}"
                    );
                }

                $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
                $queryBuilder->setParameter($placeholder, $identifiers[$identifierProperty], $doctrineClassMetadata->getTypeOfField($identifierProperty));
                $previousAlias = $joinAlias;
                $previousIdentifier = $identifierProperty;
                ++$i;
                continue;
            }

            $joinAlias = $queryNameGenerator->generateJoinAlias($alias);
            $queryBuilder->join("{$previousAlias}.{$link->getToProperty()}", $joinAlias);
            $queryBuilder->andWhere("$joinAlias.$identifierProperty = :$placeholder");
            $queryBuilder->setParameter($placeholder, $identifiers[$identifierProperty], $doctrineClassMetadata->getTypeOfField($identifierProperty));
            $previousAlias = $joinAlias;
            $previousIdentifier = $identifierProperty;
            ++$i;
        }

        if ($expressions) {
            $i = 0;
            $clause = '';
            foreach ($expressions as $alias => $expression) {
                if ($i === 0) {
                    $clause .= "$alias IN (" . $expression;
                    $i++;
                    continue;
                }

                $clause .= " AND $alias IN (" . $expression;
                $i++;
            }

            $queryBuilder->andWhere($clause . str_repeat(')', $i)); 
        }
    }
}
