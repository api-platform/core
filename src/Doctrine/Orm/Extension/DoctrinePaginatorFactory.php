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

namespace ApiPlatform\Doctrine\Orm\Extension;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrinePaginatorFactory
{
    /**
     * @param Query|QueryBuilder $query
     * @param bool               $fetchJoinCollection
     */
    public function getPaginator($query, $fetchJoinCollection): Paginator
    {
        return new Paginator($query, $fetchJoinCollection);
    }
}
