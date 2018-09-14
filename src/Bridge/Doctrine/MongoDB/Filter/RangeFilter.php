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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\RangeFilterTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Filters the collection by range.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class RangeFilter extends AbstractContextAwareFilter implements RangeFilterInterface
{
    use RangeFilterTrait;

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $values, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = [])
    {
        if (
            !\is_array($values) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass)
        ) {
            return;
        }

        $field = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        foreach ($values as $operator => $value) {
            $this->addMatch(
                $aggregationBuilder,
                $field,
                $operator,
                $value
            );
        }
    }

    /**
     * Adds the match stage according to the operator.
     */
    protected function addMatch(Builder $aggregationBuilder, string $field, string $operator, string $value)
    {
        switch ($operator) {
            case self::PARAMETER_BETWEEN:
                $rangeValue = explode('..', $value);

                $rangeValue = $this->normalizeBetweenValues($rangeValue, $field);
                if (null === $rangeValue) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->lte($rangeValue[0])->gte($rangeValue[1]);

                break;
            case self::PARAMETER_GREATER_THAN:
                $value = $this->normalizeValue($value, $field, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->gt($value);

                break;
            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                $value = $this->normalizeValue($value, $field, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->gte($value);

                break;
            case self::PARAMETER_LESS_THAN:
                $value = $this->normalizeValue($value, $field, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->lt($value);

                break;
            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                $value = $this->normalizeValue($value, $field, $operator);
                if (null === $value) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->lte($value);

                break;
        }
    }
}
