<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\FilterInterface;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;
    /**
     * @var string|null
     */
    private $order;
    /**
     * @var string
     */
    private $pageParameter;
    /**
     * @var bool
     */
    private $enableClientRequestItemsPerPage;
    /**
     * @var string
     */
    private $itemsPerPageParameter;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string|null     $order
     * @param string          $pageParameter
     * @param bool            $enableClientRequestItemsPerPage
     * @param string          $itemsPerPageParameter
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        $order,
        $pageParameter,
        $enableClientRequestItemsPerPage,
        $itemsPerPageParameter
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->order = $order;
        $this->pageParameter = $pageParameter;
        $this->enableClientRequestItemsPerPage = $enableClientRequestItemsPerPage;
        $this->itemsPerPageParameter = $itemsPerPageParameter;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false)
    {
        $entityClass = $resource->getEntityClass();
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        if ($fetchData || !method_exists($manager, 'getReference')) {
            return $manager->find($entityClass, $id);
        }

        return $manager->getReference($entityClass, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResourceInterface $resource, Request $request)
    {
        $entityClass = $resource->getEntityClass();

        $manager = $this->managerRegistry->getManagerForClass($resource->getEntityClass());
        $repository = $manager->getRepository($entityClass);
        $queryBuilder = $repository->createQueryBuilder('o');

        $paginationEnabled = $resource->isPaginationEnabled();
        if ($paginationEnabled) {
            $page = (int)$request->get($this->pageParameter, 1);

            if ($this->enableClientRequestItemsPerPage && $requestedItemsPerPage = $request->get($this->itemsPerPageParameter)) {
                $itemsPerPage = (int)$requestedItemsPerPage;
            } else {
                $itemsPerPage = $resource->getItemsPerPage();
            }

            $queryBuilder
                ->setFirstResult(($page - 1) * $itemsPerPage)
                ->setMaxResults($itemsPerPage)
            ;
        }

        foreach ($resource->getFilters() as $filter) {
            if ($filter instanceof FilterInterface) {
                $filter->apply($resource, $queryBuilder, $request);
            }
        }

        if (null !== $this->order) {
            $queryBuilder->addOrderBy('o.id', $this->order);
        }

        if ($paginationEnabled) {
            return new Paginator(new DoctrineOrmPaginator($queryBuilder));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResourceInterface $resource)
    {
        return null !== $this->managerRegistry->getManagerForClass($resource->getEntityClass());
    }
}
