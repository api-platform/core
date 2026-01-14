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
        $field = $alias.'.'.$property;
        $values = $parameter->getValue();

        if (!is_iterable($values)) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $value = $this->caseSensitive ? $values : strtolower($values);
            $queryBuilder->setParameter($parameterName, $this->formatLikeValue($value));

            $likeExpression = $this->caseSensitive
                ? $field.' LIKE :'.$parameterName.' ESCAPE \'\\\''
                : 'LOWER('.$field.') LIKE :'.$parameterName.' ESCAPE \'\\\'';
            $queryBuilder->{$context['whereClause'] ?? 'andWhere'}($likeExpression);

            return;
        }

        $likeExpressions = [];
        foreach ($values as $value) {
            $parameterName = $queryNameGenerator->generateParameterName($property);
            $likeExpressions[] = $this->caseSensitive
                ? $field.' LIKE :'.$parameterName.' ESCAPE \'\\\''
                : 'LOWER('.$field.') LIKE :'.$parameterName.' ESCAPE \'\\\'';

            $val = $this->caseSensitive ? $value : strtolower($value);
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
