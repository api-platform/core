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
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;

final class PartialSearchFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    use OpenApiFilterTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (!$parameter = $context['parameter'] ?? null) {
            return;
        }

        $value = $parameter->getValue();

        $property = $parameter->getProperty();
        $alias = $queryBuilder->getRootAliases()[0];
        $field = $alias.'.'.$property;

        $parameterName = $queryNameGenerator->generateParameterName($property);

        $likeExpression = $queryBuilder->expr()->like(
            'LOWER('.$field.')',
            ':'.$parameterName
        );

        $queryBuilder
            ->andWhere($likeExpression)
            ->setParameter($parameterName, '%'.strtolower($value).'%');
    }
}
