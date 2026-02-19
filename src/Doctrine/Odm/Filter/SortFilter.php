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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Common\Filter\OrderFilterInterface;
use ApiPlatform\Doctrine\Odm\NestedPropertyHelperTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Parameter-based order filter for sorting a collection by a property.
 *
 * Unlike {@see OrderFilter}, this filter does not extend AbstractFilter and is designed
 * exclusively for use with Parameters (QueryParameter).
 *
 * Usage: `new QueryParameter(filter: new SortFilter(), property: 'department.name')`.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
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

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (null === $parameter) {
            return;
        }

        $value = $parameter->getValue(null);
        if (!\is_string($value)) {
            return;
        }

        $direction = strtoupper($value);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            return;
        }

        $property = $parameter->getProperty();
        $matchField = $this->addNestedParameterLookups($property, $aggregationBuilder, $parameter, true, $context);

        $mongoDirection = 'ASC' === $direction ? 1 : -1;

        if (null !== $nullsComparison = $this->nullsComparison) {
            $nullsDirection = OrderFilterInterface::NULLS_DIRECTION_MAP[$nullsComparison][$direction] ?? null;
            if (null !== $nullsDirection) {
                $nullRankField = \sprintf('_null_rank_%s', str_replace('.', '_', $matchField));
                $mongoNullsDirection = 'ASC' === $nullsDirection ? 1 : -1;

                $aggregationBuilder->addFields()
                    ->field($nullRankField)
                    ->cond(
                        $aggregationBuilder->expr()->eq('$'.$matchField, null),
                        0,
                        1
                    );

                $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$nullRankField => $mongoNullsDirection];
            }
        }

        $aggregationBuilder->sort(
            $context['mongodb_odm_sort_fields'] = ($context['mongodb_odm_sort_fields'] ?? []) + [$matchField => $mongoDirection]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['asc', 'desc', 'ASC', 'DESC']];
    }
}
