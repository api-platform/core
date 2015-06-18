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
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Doctrine\ORM\QueryBuilder;
use Dunglas\ApiBundle\Doctrine\Orm\Filter\FilterInterface;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Model\PaginationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProvider implements DataProviderInterface
{
    use PaginationTrait;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;
    /**
     * @var string|null
     */
    private $order;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string|null     $order
     */
    public function __construct(ManagerRegistry $managerRegistry, $order)
    {
        $this->managerRegistry = $managerRegistry;
        $this->order = $order;
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

        if ($paginationEnabled = $this->isPaginationEnabled($resource, $request)) {
            $itemsPerPage = $this->getItemsPerPage($resource, $request);

            $queryBuilder
                ->setFirstResult(($this->getPage($resource, $request) - 1) * $itemsPerPage)
                ->setMaxResults($itemsPerPage)
            ;
        }

        foreach ($resource->getFilters() as $filter) {
            if ($filter instanceof FilterInterface) {
                $filter->apply($resource, $queryBuilder, $request);
            }
        }

        $classMetaData = $manager->getClassMetadata($entityClass);
        $identifiers = $classMetaData->getIdentifier();

        foreach ($classMetaData->getAssociationNames() as $i => $association) {
            $mapping = $classMetaData->associationMappings[$association];

            if (ClassMetadataInfo::FETCH_EAGER === $mapping['fetch']) {
                $queryBuilder->leftJoin('o.'.$association, 'a'.$i);
                $queryBuilder->addSelect('a'.$i);
            }
        }

        if (null !== $this->order && 1 === count($identifiers)) {
            $identifier = $identifiers[0];
            $queryBuilder->addOrderBy('o.'.$identifier, $this->order);
        }

        if ($paginationEnabled) {
            return $this->getPaginator($queryBuilder);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Gets the paginator.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return Paginator
     */
    protected function getPaginator(QueryBuilder $queryBuilder)
    {
        $doctrineOrmPaginator = new DoctrineOrmPaginator($queryBuilder);
        // Disable output walkers by default (performance)
        $doctrineOrmPaginator->setUseOutputWalkers(false);

        return new Paginator($doctrineOrmPaginator);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResourceInterface $resource)
    {
        return null !== $this->managerRegistry->getManagerForClass($resource->getEntityClass());
    }
}
