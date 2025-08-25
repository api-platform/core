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

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];
        $property = $parameter->getProperty();
        $values = $parameter->getValue();

        if (!is_iterable($values)) {
            $escapedValue = preg_quote($values, '/');
            $aggregationBuilder
                ->match()
                ->field($property)
                ->equals(new Regex($escapedValue, 'i'));

            return;
        }

        $match = $aggregationBuilder->match();
        foreach ($values as $value) {
            $escapedValue = preg_quote($value, '/');

            $match->addOr(
                $match->expr()
                    ->field($property)
                    ->equals(new Regex($escapedValue, 'i'))
            );
        }
    }
}
