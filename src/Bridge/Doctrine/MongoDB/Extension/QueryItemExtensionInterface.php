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

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\QueryNameGeneratorInterface;
use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * Interface of Doctrine ODM query extensions for item queries.
 *
 * @author Maxime STEINHAUSSER <maxime.steinhausser@gmail.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface QueryItemExtensionInterface
{
    public function applyToItem(Builder $aggregationBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null);
}
