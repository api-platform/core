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

        if ($linkClass = $context['linkClass'] ?? false) {
            foreach ($links as $link) {
                if ($linkClass === $link->getFromClass()) {
                    foreach ($identifiers as $identifier => $value) {
                        $this->applyLink($queryBuilder, $queryNameGenerator, $doctrineClassMetadata, $alias, $link, $identifier, $value);
                    }

                    return;
                }
            }

            $operation = $this->resourceMetadataCollectionFactory->create($linkClass)->getOperation($operationName);
            $links = $operation instanceof GraphQlOperation ? $operation->getLinks() : $operation->getUriVariables();
            foreach ($links as $link) {
                if ($resourceClass === $link->getFromClass()) {
                    $link = $link->withFromProperty($link->getToProperty())->withFromClass($linkClass);
                    foreach ($identifiers as $identifier => $value) {
                        $this->applyLink($queryBuilder, $queryNameGenerator, $doctrineClassMetadata, $alias, $link, $identifier, $value);
                    }

                    return;
                }
            }

            throw new RuntimeException(sprintf('The class "%s" cannot be retrieved from "%s".', $resourceClass, $linkClass));
        }

        if (!$links) {
            return;
        }

        foreach ($identifiers as $identifier => $value) {
            $link = $links[$identifier] ?? $links['id'];

            $this->applyLink($queryBuilder, $queryNameGenerator, $doctrineClassMetadata, $alias, $link, $identifier, $value);
        }
    }

    private function applyLink(QueryBuilder $queryBuilder, QueryNameGenerator $queryNameGenerator, ClassMetadata $doctrineClassMetadata, string $alias, Link $link, string $identifier, $value)
    {
        $placeholder = ':id_'.$identifier;
        if ($fromProperty = $link->getFromProperty()) {
            $propertyIdentifier = $link->getIdentifiers()[0];
            $joinAlias = $queryNameGenerator->generateJoinAlias($fromProperty);

            $queryBuilder->join(
                $link->getFromClass(),
                $joinAlias,
                'with',
                "$alias.$propertyIdentifier = $joinAlias.$fromProperty"
            );

            $expression = $queryBuilder->expr()->eq(
                "{$joinAlias}.{$propertyIdentifier}",
                $placeholder
            );
        } elseif ($property = $link->getToProperty()) {
            $propertyIdentifier = $link->getIdentifiers()[0];
            $joinAlias = $queryNameGenerator->generateJoinAlias($property);

            $queryBuilder->join(
                "$alias.$property",
                $joinAlias,
                );

            $expression = $queryBuilder->expr()->eq(
                "{$joinAlias}.{$propertyIdentifier}",
                $placeholder
            );
        } else {
            $expression = $queryBuilder->expr()->eq(
                "{$alias}.{$identifier}", $placeholder
            );
        }
        $queryBuilder->andWhere($expression);
        $queryBuilder->setParameter($placeholder, $value, $doctrineClassMetadata->getTypeOfField($identifier));
    }
}
