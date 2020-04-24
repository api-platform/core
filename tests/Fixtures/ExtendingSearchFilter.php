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

namespace ApiPlatform\Core\Tests\Fixtures;

use ApiPlatform\Core\Bridge\Doctrine\Common\Filter\SearchFilterInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Doctrine\ORM\QueryBuilder;

class ExtendingSearchFilter extends SearchFilter implements SearchFilterInterface
{
    public function __construct(QueryBuilder $queryBuilder)
    {
        parent::addWhereByStrategy(self::STRATEGY_EXACT, $queryBuilder, new QueryNameGenerator(), 'o', 'name', 'test', false);
    }
}
