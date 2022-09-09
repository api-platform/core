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

namespace ApiPlatform\Doctrine\Odm\Extension;

use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Interface of Doctrine MongoDB ODM aggregation extensions for collection aggregations.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface AggregationCollectionExtensionInterface
{
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, Operation $operation = null, array &$context = []);
}
