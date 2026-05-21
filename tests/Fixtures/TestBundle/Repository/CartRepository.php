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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Repository;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Cart;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends EntityRepository<Cart>
 */
class CartRepository extends EntityRepository
{
    public function getCartsWithTotalQuantity(): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->leftJoin('o.items', 'items')
            ->addSelect('COALESCE(SUM(items.quantity), 0) AS totalQuantity')
            ->addGroupBy('o.id');

        return $queryBuilder;
    }
}
