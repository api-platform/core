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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class PartialSearchFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use OpenApiFilterTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'];
        $property = $parameter->getProperty();
        $alias = $queryBuilder->getRootAliases()[0];
        $field = $alias.'.'.$property;
        $parameterName = $queryNameGenerator->generateParameterName($property);
        $values = $parameter->getValue();

        if (!is_iterable($values)) {
            $queryBuilder->setParameter($parameterName, '%'.strtolower($values).'%');

            $queryBuilder->{$context['whereClause'] ?? 'andWhere'}($queryBuilder->expr()->like(
                'LOWER('.$field.')',
                ':'.$parameterName
            ));

            return;
        }

        $likeExpressions = [];
        foreach ($values as $val) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $likeExpressions[] = $queryBuilder->expr()->like(
                'LOWER('.$field.')',
                ':'.$parameterName
            );
            $queryBuilder->setParameter($parameterName, '%'.strtolower($val).'%');
        }

        $queryBuilder->{$context['whereClause'] ?? 'andWhere'}(
            $queryBuilder->expr()->orX(...$likeExpressions)
        );
    }
}
