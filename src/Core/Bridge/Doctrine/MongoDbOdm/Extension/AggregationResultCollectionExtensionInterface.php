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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Interface of Doctrine MongoDB ODM aggregation extensions that supports result production
 * for specific cases such as pagination.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface AggregationResultCollectionExtensionInterface extends AggregationCollectionExtensionInterface
{
    public function supportsResult(string $resourceClass, string $operationName = null, array $context = []): bool;

    public function getResult(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = []);
}
