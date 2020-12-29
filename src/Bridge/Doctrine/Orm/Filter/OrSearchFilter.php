<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;

/**
 * Filter the collection using multiple fields at the same time.
 *
 * @author Daniel West <daniel@silverback.is>
 */
class OrSearchFilter extends SearchFilter
{
    protected function filterProperty(string $property, $values, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null, bool $useOrWhere = false): void
    {
        if ($property !== 'or') {
            return;
        }

        $queryJoinParts = [];
        $ors = [];
        foreach ($values as $fields=>$value) {
            $subQueryBuilder = clone $queryBuilder;
            $subQueryBuilder->resetDQLPart('where');

            $orProperties = explode(',', $fields);
            foreach ($orProperties as $orProperty) {
                parent::filterProperty($orProperty, $value, $subQueryBuilder, $queryNameGenerator, $resourceClass, $operationName, true);
            }

            $queryJoinParts[] = $subQueryBuilder->getDQLPart('join');
            $ors[] = $subQueryBuilder->getDQLPart('where');

            foreach ($subQueryBuilder->getParameters() as $parameter) {
                /** @var Parameter $parameter */
                $queryBuilder->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
            }
        }
        $queryBuilder->resetDQLPart('join');
        foreach ($queryJoinParts as $joinParts) {
            foreach ($joinParts as $alias=>$joins) {
                foreach ($joins as $join) {
                    $queryBuilder->add('join', [$alias=>$join], true);
                }
            }
        }
        $queryBuilder->andWhere($queryBuilder->expr()->andX(...$ors));
    }
}
