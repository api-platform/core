<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * Interface of Doctrine Doctrine ODM query extensions for item queries.
 *
 * @author Maxime STEINHAUSSER <maxime.steinhausser@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryItemExtensionInterface
{
    /**
     * @param Builder     $queryBuilder
     * @param string      $resourceClass
     * @param array       $identifiers
     * @param string|null $operationName
     */
    public function applyToItem(Builder $queryBuilder, string $resourceClass, array $identifiers, string $operationName = null);
}
