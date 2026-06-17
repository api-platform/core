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
use ApiPlatform\Doctrine\Odm\NestedPropertyHelperTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use MongoDB\BSON\Regex;

/**
 * Filters the collection by a word boundary prefix, matching documents that contain a word starting with the value,
 * using a regular expression anchored at the start of the string or at a word boundary.
 */
final class WordStartSearchFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use NestedPropertyHelperTrait;
    use OpenApiFilterTrait;

    public function __construct(private readonly bool $caseSensitive = true)
    {
    }

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];

        if (null === $parameter->getProperty()) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property. Please provide the property explicitly.', $parameter->getKey()));
        }

        $property = $parameter->getProperty();
        $values = $parameter->getValue();
        $match = $context['match'] = $context['match'] ??
            $aggregationBuilder
            ->matchExpr();
        $operator = $context['operator'] ?? 'addAnd';

        $matchField = $this->addNestedParameterLookups($property, $aggregationBuilder, $parameter, false, $context);

        if (!is_iterable($values)) {
            $match->{$operator}(
                $aggregationBuilder->matchExpr()->field($matchField)->equals($this->createRegex($values))
            );

            return;
        }

        $or = $aggregationBuilder->matchExpr();
        foreach ($values as $value) {
            $or->addOr(
                $aggregationBuilder->matchExpr()
                    ->field($matchField)
                    ->equals($this->createRegex($value))
            );
        }

        $match->{$operator}($or);
    }

    private function createRegex(string $value): Regex
    {
        $escapedValue = preg_quote($value, '/');

        return new Regex('(^'.$escapedValue.'|\s'.$escapedValue.')', $this->caseSensitive ? '' : 'i');
    }
}
