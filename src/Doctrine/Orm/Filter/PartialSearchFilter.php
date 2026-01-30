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
use ApiPlatform\Doctrine\Orm\Util\QueryBuilderHelper;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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

        if (null === $property = $parameter->getProperty()) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property. Please provide the property explicitly.', $parameter->getKey()));
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $field = $alias.'.'.$property;
        $values = $parameter->getValue();

        if (str_contains($property, '.')) {
            $associations = explode('.', $property);
            $field = array_pop($associations);
            $currentAlias = $alias;
            
            foreach ($associations as $association) {
                $currentAlias = QueryBuilderHelper::addJoinOnce(
                    $queryBuilder,
                    $queryNameGenerator,
                    $currentAlias,
                    $association
                );
            }
            $alias = $currentAlias;
        }

        if (!is_iterable($values)) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $queryBuilder->setParameter($parameterName, $this->formatLikeValue($values));

            $likeExpression = 'LOWER('.$field.') LIKE LOWER(:'.$parameterName.') ESCAPE \'\\\'';
            $queryBuilder->{$context['whereClause'] ?? 'andWhere'}($likeExpression);

            return;
        }

        $likeExpressions = [];
        foreach ($values as $val) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $likeExpressions[] = 'LOWER('.$field.') LIKE LOWER(:'.$parameterName.') ESCAPE \'\\\'';
            $queryBuilder->setParameter($parameterName, $this->formatLikeValue($val));
        }

        $queryBuilder->{$context['whereClause'] ?? 'andWhere'}(
            $queryBuilder->expr()->orX(...$likeExpressions)
        );
    }

    private function formatLikeValue(string $value): string
    {
        return '%'.addcslashes($value, '\\%_').'%';
    }
}
