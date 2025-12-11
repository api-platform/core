<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
/**
 * @extends EntityRepository<Cart::class>
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
