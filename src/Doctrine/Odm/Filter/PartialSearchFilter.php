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
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use MongoDB\BSON\Regex;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class PartialSearchFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
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

        if (!is_iterable($values)) {
            $escapedValue = preg_quote($values, '/');
            $match->{$operator}(
                $aggregationBuilder->matchExpr()->field($property)->equals(new Regex($escapedValue, $this->caseSensitive ? '' : 'i'))
            );

            return;
        }

        $or = $aggregationBuilder->matchExpr();
        foreach ($values as $value) {
            $escapedValue = preg_quote($value, '/');

            $or->addOr(
                $aggregationBuilder->matchExpr()
                    ->field($property)
                    ->equals(new Regex($escapedValue, $this->caseSensitive ? '' : 'i'))
            );
        }

        $match->{$operator}($or);
    }
}
