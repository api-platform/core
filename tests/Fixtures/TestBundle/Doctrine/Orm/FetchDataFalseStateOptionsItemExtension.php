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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Doctrine\Orm;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FetchDataFalseStateOptionsEntity;
use Doctrine\ORM\QueryBuilder;

/**
 * Excludes every FetchDataFalseStateOptionsEntity row from item queries. A fetch_data=false request must
 * return a getReference() and skip the query entirely, so this extension must never run for it; if it does,
 * the item resolves to null. Scoped to the fixture entity so it does not affect other resources.
 */
final class FetchDataFalseStateOptionsItemExtension implements QueryItemExtensionInterface
{
    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
    {
        if (FetchDataFalseStateOptionsEntity::class !== $resourceClass) {
            return;
        }

        $queryBuilder->andWhere('1 = 0');
    }
}
