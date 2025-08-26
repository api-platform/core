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

use ApiPlatform\Metadata\FilterInterface as BaseFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Doctrine MongoDB ODM filter interface.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * Applies the filter.
     *
     * @param array|array{filters?: array<string, mixed>|array, parameter?: Parameter, mongodb_odm_sort_fields?: array, ...} $context
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void;
}
