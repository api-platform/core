<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Doctrine\Orm\Extension;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface of Doctrine ORM query extensions for item queries.
 *
 * @author Maxime STEINHAUSSER <maxime.steinhausser@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryItemExtensionInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string       $resourceClass
     * @param array        $identifiers
     * @param string|null  $operationName
     */
    public function applyToItem(QueryBuilder $queryBuilder, string $resourceClass, array $identifiers, string $operationName = null);
}
