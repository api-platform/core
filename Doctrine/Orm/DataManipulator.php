<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Dunglas\JsonLdApiBundle\Resource;

/**
 * Manipulates data through Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataManipulator
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;
    /**
     * @var int
     */
    private $defaultByPage;
    /**
     * @var string
     */
    private $defaultOrder;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param int             $defaultByPage
     * @param string|null     $defaultOrder
     */
    public function __construct(ManagerRegistry $managerRegistry, $defaultByPage, $defaultOrder)
    {
        $this->managerRegistry = $managerRegistry;
        $this->defaultByPage = $defaultByPage;
        $this->defaultOrder = $defaultOrder;
    }

    /**
     * Retrieves a collection.
     *
     * @param Resource    $resource
     * @param int         $page
     * @param array       $filters
     * @param int|null    $byPage
     * @param string|null $order
     *
     * @return Paginator
     */
    public function getCollection(Resource $resource, $page, array $filters, $byPage = null, $order = null)
    {
        if (!$byPage) {
            $byPage = $this->defaultByPage;
        }

        if (!$order) {
            $order = $this->defaultOrder;
        }

        $repository = $this->managerRegistry->getManagerForClass($resource->getEntityClass())->getRepository($resource->getEntityClass());

        /**
         * @var \Doctrine\ORM\QueryBuilder
         */
        $queryBuilder = $repository
            ->createQueryBuilder('o')
            ->setFirstResult(($page - 1) * $byPage)
            ->setMaxResults($byPage)
        ;

        foreach ($filters as $filter) {
            $queryBuilder
                ->andWhere(sprintf('o.%1$s LIKE :%1$s', $filter['name']))
                ->setParameter($filter['name'], $filter['exact'] ? $filter['value'] : sprintf('%%%s%%', $filter['value']))
            ;
        }

        if ($order) {
            $queryBuilder->addOrderBy('o.id', $order);
        }

        return new Paginator($queryBuilder);
    }
}
