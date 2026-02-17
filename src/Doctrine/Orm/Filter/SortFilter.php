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
use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Orm\NestedPropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Parameter-based order filter for sorting a collection by a property.
 *
 * Unlike {@see OrderFilter}, this filter does not extend AbstractFilter and is designed
 * exclusively for use with Parameters (QueryParameter).
 *
 * Usage: `new QueryParameter(filter: new SortFilter(), property: 'department.name')`.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SortFilter implements FilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use NestedPropertyHelperTrait;
    use OpenApiFilterTrait;

    public function __construct(
        private readonly ?string $nullsComparison = null,
    ) {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (null === $parameter) {
            return;
        }

        $value = $context['filters'][$parameter->getProperty() ?? ''] ?? null;
        if (null === $value) {
            return;
        }

        $direction = strtoupper($value);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            return;
        }

        $property = $parameter->getProperty();
        $alias = $queryBuilder->getRootAliases()[0];

        [$alias, $field] = $this->addNestedParameterJoins($property, $alias, $queryBuilder, $queryNameGenerator, $parameter, Join::LEFT_JOIN);

        if (null !== $nullsComparison = $this->nullsComparison) {
            $nullsDirection = OrderFilterInterface::NULLS_DIRECTION_MAP[$nullsComparison][$direction];
            $nullRankHiddenField = \sprintf('_%s_%s_null_rank', $alias, str_replace('.', '_', $field));
            $queryBuilder->addSelect(\sprintf('CASE WHEN %s.%s IS NULL THEN 0 ELSE 1 END AS HIDDEN %s', $alias, $field, $nullRankHiddenField));
            $queryBuilder->addOrderBy($nullRankHiddenField, $nullsDirection);
        }

        $queryBuilder->addOrderBy(\sprintf('%s.%s', $alias, $field), $direction);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['asc', 'desc', 'ASC', 'DESC']];
    }
}
