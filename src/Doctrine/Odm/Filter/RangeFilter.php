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

use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Doctrine\Common\Filter\RangeFilterTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Filters the collection by range.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class RangeFilter extends AbstractFilter implements RangeFilterInterface
{
    use RangeFilterTrait;

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, Builder $aggregationBuilder, string $resourceClass, Operation $operation = null, array &$context = []): void
    {
        if (
            !\is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $values = $this->normalizeValues($values, $property);
        if (null === $values) {
            return;
        }

        $matchField = $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        foreach ($values as $operator => $value) {
            $this->addMatch(
                $aggregationBuilder,
                $field,
                $matchField,
                $operator,
                $value
            );
        }
    }

    /**
     * Adds the match stage according to the operator.
     */
    protected function addMatch(Builder $aggregationBuilder, string $field, string $matchField, string $operator, string $value): void
    {
        switch ($operator) {
            case self::PARAMETER_BETWEEN:
                $rangeValue = explode('..', $value);

                $rangeValue = $this->normalizeBetweenValues($rangeValue);
                if (null === $rangeValue) {
                    return;
                }

                if ($rangeValue[0] === $rangeValue[1]) {
                    $aggregationBuilder->match()->field($matchField)->equals($rangeValue[0]);

                    return;
                }

                $aggregationBuilder->match()->field($matchField)->gte($rangeValue[0])->lte($rangeValue[1]);

                break;
            case self::PARAMETER_GREATER_THAN:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($matchField)->gt($value);

                break;
            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($matchField)->gte($value);

                break;
            case self::PARAMETER_LESS_THAN:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($matchField)->lt($value);

                break;
            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                $value = $this->normalizeValue($value, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($matchField)->lte($value);

                break;
        }
    }
}
