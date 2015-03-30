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
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrineOrmPaginator;
use Dunglas\JsonLdApiBundle\Model\DataProviderInterface;
use Dunglas\JsonLdApiBundle\JsonLd\ResourceInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * @var ResourceInterface
     */
    private $resource;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function initResource(ResourceInterface $resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($id, $fetchData = false)
    {
        $entityClass = $this->resource->getEntityClass();
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        if ($fetchData || !method_exists($manager, 'getReference')) {
            return $manager->find($entityClass, $id);
        }

        return $manager->getReference($entityClass, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection($page, array $filters, $itemsPerPage = 30, $order = null)
    {
        $manager = $this->managerRegistry->getManagerForClass($this->resource->getEntityClass());
        $repository = $manager->getRepository($this->resource->getEntityClass());
        if (count($filters)) {
            $metadata = $manager->getClassMetadata($this->resource->getEntityClass());
            $fieldNames = array_flip($metadata->getFieldNames());
        }

        /*
         * @var \Doctrine\ORM\QueryBuilder
         */
        $queryBuilder = $repository
            ->createQueryBuilder('o')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        foreach ($filters as $filter) {
            if (isset($fieldNames[$filter['name']])) {
                if ('id' === $filter['name']) {
                    $filter['value'] = $this->getFilterValueFromUrl($filter['value']);
                }

                $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $filter['name']))
                    ->setParameter($filter['name'], $filter['exact'] ? $filter['value'] : sprintf('%%%s%%', $filter['value']))
                ;
            } elseif ($metadata->isSingleValuedAssociation($filter['name']) || $metadata->isCollectionValuedAssociation($filter['name'])) {
                $value = $this->getFilterValueFromUrl($filter['value']);

                $queryBuilder
                    ->join(sprintf('o.%s', $filter['name']), $filter['name'])
                    ->andWhere(sprintf('%1$s.id = :%1$s', $filter['name']))
                    ->setParameter($filter['name'], $filter['exact'] ? $value : sprintf('%%%s%%', $value))
                ;
            }
        }

        if ($order) {
            $queryBuilder->addOrderBy('o.id', $order);
        }

        return new Paginator(new DoctrineOrmPaginator($queryBuilder));
    }

    /**
     * Gets the ID from an URI or a raw ID.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function getFilterValueFromUrl($value)
    {
        try {
            if ($item = $this->resource->getResourceCollection()->getItemFromUri($value)) {
                return $item->getId();
            }
        } catch (ExceptionInterface $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
