<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Doctrine;

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

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Retrieves a collection.
     *
     * @param Resource $resource
     * @param int      $page
     * @param int      $byPage
     * @param array    $filters
     *
     * @return Paginator
     */
    public function getCollection(Resource $resource, $page, $byPage, array $filters)
    {
        $repository = $this->managerRegistry->getManagerForClass($resource->getEntityClass())->getRepository($resource->getEntityClass());

        /**
         * @var $queryBuilder \Doctrine\ORM\QueryBuilder
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

        return new Paginator($queryBuilder);
    }
}
