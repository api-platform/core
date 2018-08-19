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
 * Context aware extension.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface ContextAwareQueryCollectionExtensionInterface extends QueryCollectionExtensionInterface
{
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array $context = []);
}
