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
use Doctrine\ORM\QueryBuilder;

trait UriVariablesHandlerTrait
{
    private function handleUriVariables(QueryBuilder $queryBuilder, array $identifiers, QueryNameGenerator $queryNameGenerator, array $context, string $resourceClass, ?string $operationName = null): void
    {
        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);
        $uriVariables = $operation->getUriVariables();

        if (null === $uriVariables) {
            return;
        }
        $alias = $queryBuilder->getRootAliases()[0];

        foreach ($identifiers as $identifier => $value) {
            $uriVariable = $uriVariables[$identifier] ?? $uriVariables['id'];

            $placeholder = ':id_'.$identifier;
            if ($inverseProperty = $uriVariable->getInverseProperty()) {
                $propertyIdentifier = $uriVariable->getIdentifiers()[0];
                $joinAlias = $queryNameGenerator->generateJoinAlias($inverseProperty);

                $queryBuilder->join(
                    $uriVariable->getTargetClass(),
                    $joinAlias,
                    'with',
                    "$alias.$propertyIdentifier = $joinAlias.$inverseProperty"
                );

                $expression = $queryBuilder->expr()->eq(
                    "{$joinAlias}.{$propertyIdentifier}",
                    $placeholder
                );
            } elseif ($property = $uriVariable->getProperty()) {
                $propertyIdentifier = $uriVariable->getIdentifiers()[0];
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
                $propertyIdentifier = $uriVariable->getIdentifiers()[0];
                $expression = $queryBuilder->expr()->eq(
                    "{$alias}.{$propertyIdentifier}", $placeholder
                );
            }
            $queryBuilder->andWhere($expression);
            $queryBuilder->setParameter($placeholder, $value);
        }
    }
}
