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
use ApiPlatform\Doctrine\Orm\NestedPropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

/**
 * Filters the collection by a word boundary prefix, matching fields that contain a word starting with the value,
 * using a `LIKE 'value%' OR LIKE '% value%'` clause.
 */
final class WordStartSearchFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use NestedPropertyHelperTrait;
    use OpenApiFilterTrait;

    public function __construct(private readonly bool $caseSensitive = false)
    {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'];

        if (null === $parameter->getProperty()) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property. Please provide the property explicitly.', $parameter->getKey()));
        }

        $property = $parameter->getProperty();
        $alias = $queryBuilder->getRootAliases()[0];
        [$alias, $property] = $this->addNestedParameterJoins($property, $alias, $queryBuilder, $queryNameGenerator, $parameter);
        $field = $alias.'.'.$property;
        $values = $parameter->getValue();

        if (!is_iterable($values)) {
            $values = [$values];
        }

        $expressions = [];
        foreach ($values as $val) {
            $startName = $queryNameGenerator->generateParameterName($property);
            $wordName = $queryNameGenerator->generateParameterName($property);

            $expressions[] = $queryBuilder->expr()->orX(
                $this->createLikeExpression($field, $startName),
                $this->createLikeExpression($field, $wordName),
            );

            $queryBuilder->setParameter($startName, $this->formatStartValue($val));
            $queryBuilder->setParameter($wordName, $this->formatWordValue($val));
        }

        $queryBuilder->{$context['whereClause'] ?? 'andWhere'}(
            $queryBuilder->expr()->orX(...$expressions)
        );
    }

    private function createLikeExpression(string $field, string $parameterName): string
    {
        return $this->caseSensitive
            ? $field.' LIKE :'.$parameterName.' ESCAPE \'\\\''
            : 'LOWER('.$field.') LIKE LOWER(:'.$parameterName.') ESCAPE \'\\\'';
    }

    private function formatStartValue(string $value): string
    {
        return addcslashes($value, '\\%_').'%';
    }

    private function formatWordValue(string $value): string
    {
        return '% '.addcslashes($value, '\\%_').'%';
    }
}
