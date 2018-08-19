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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Interface of Doctrine MongoDB ODM query extensions for collection queries.
 *
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface QueryCollectionExtensionInterface
{
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null);
}
