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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\ExistsFilterTrait;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Filters the collection by whether a property value exists or not.
 *
 * For each property passed, if the resource does not have such property or if
 * the value is not one of ( "true" | "false" | "1" | "0" ) the property is ignored.
 *
 * A query parameter with key but no value is treated as `true`, e.g.:
 * Request: GET /products?brand[exists]
 * Interpretation: filter products which have a brand
 *
 * @experimental
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ExistsFilter extends AbstractFilter implements ExistsFilterInterface
{
    use ExistsFilterTrait;

    /**
     * {@inheritdoc}
     */
    protected function filterProperty(string $property, $value, Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        if (
            !isset($value[self::QUERY_PARAMETER_KEY]) ||
            !$this->isPropertyEnabled($property, $resourceClass) ||
            !$this->isPropertyMapped($property, $resourceClass, true) ||
            !$this->isNullableField($property, $resourceClass)
        ) {
            return;
        }

        $value = $this->normalizeValue($value, $property);
        if (null === $value) {
            return;
        }

        $matchField = $property;

        if ($this->isPropertyNested($property, $resourceClass)) {
            [$matchField] = $this->addLookupsForNestedProperty($property, $aggregationBuilder, $resourceClass);
        }

        $aggregationBuilder->match()->field($matchField)->{$value ? 'notEqual' : 'equals'}(null);
    }

    /**
     * {@inheritdoc}
     */
    protected function isNullableField(string $property, string $resourceClass): bool
    {
        $propertyParts = $this->splitPropertyParts($property, $resourceClass);
        $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);

        $field = $propertyParts['field'];

        return $metadata instanceof ClassMetadata && $metadata->hasField($field) ? $metadata->isNullable($field) : false;
    }
}
