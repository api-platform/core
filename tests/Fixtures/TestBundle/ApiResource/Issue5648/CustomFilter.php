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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5648;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class CustomFilter extends AbstractFilter
{
    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if ('custom' !== $property) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $secondAlias = $queryNameGenerator->generateJoinAlias('relatedDummies');

        $joinCondition = $queryBuilder->expr()->like(sprintf('%s.name', $secondAlias), ':param');

        $queryBuilder->join(sprintf('%s.relatedDummies', $alias), $secondAlias, Join::WITH, $joinCondition)
        ->setParameter('param', '%'.$value.'%')
        ->andWhere('1=1'); // problem only gets triggered when there is a where part.
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
