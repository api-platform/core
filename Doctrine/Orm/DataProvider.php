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
use Dunglas\JsonLdApiBundle\Api\Filter\FilterInterface;
use Dunglas\JsonLdApiBundle\Model\DataProviderInterface;
use Dunglas\JsonLdApiBundle\Api\ResourceInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    public function __construct(
        RouterInterface $router,
        ManagerRegistry $managerRegistry,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->router = $router;
        $this->managerRegistry = $managerRegistry;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Initializes the root data provider.
     *
     * @param DataProviderInterface $dataProvider
     */
    public function initDataProvider(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
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
    public function getCollection(ResourceInterface $resource, array $filters = [], array $order = [], $page = null, $itemsPerPage = null)
    {
        $entityClass = $resource->getEntityClass();

        $manager = $this->managerRegistry->getManagerForClass($resource->getEntityClass());
        $repository = $manager->getRepository($entityClass);

        if (count($filters)) {
            $metadata = $manager->getClassMetadata($entityClass);
            $fieldNames = array_flip($metadata->getFieldNames());
        }

        $queryBuilder = $repository
            ->createQueryBuilder('o')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        foreach ($resource->getFilters() as $enabledFilter) {
            $filterName = $enabledFilter->getName();
            $exact = FilterInterface::STRATEGY_EXACT === $enabledFilter->getStrategy();

            if (!isset($filters[$filterName])) {
                continue;
            }

            $value = $filters[$filterName];

            if (isset($fieldNames[$filterName])) {
                if ('id' === $filterName) {
                    $value = $this->getFilterValueFromUrl($value);
                }

                $queryBuilder
                    ->andWhere(sprintf('o.%1$s LIKE :%1$s', $filterName))
                    ->setParameter($filterName, $exact ? $value : sprintf('%%%s%%', $value))
                ;
            } elseif ($metadata->isSingleValuedAssociation($filterName) || $metadata->isCollectionValuedAssociation($filterName)) {
                $value = $this->getFilterValueFromUrl($value);

                $queryBuilder
                    ->join(sprintf('o.%s', $filterName), $filterName)
                    ->andWhere(sprintf('%1$s.id = :%1$s', $filterName))
                    ->setParameter($filterName, $exact ? $value : sprintf('%%%s%%', $value))
                ;
            }
        }

        foreach ($order as $key => $value) {
            $queryBuilder->addOrderBy('o.'.$key, $value);
        }

        return new Paginator(new DoctrineOrmPaginator($queryBuilder));
    }

    /**
     * {@inheritdoc}
     */
    public function getItemFromIri($iri, $fetchData = false)
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResourceInterface $resource)
    {
        return null !== $this->managerRegistry->getManagerForClass($resource->getEntityClass());
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
            if ($item = $this->dataProvider->getItemFromIri($value)) {
                return $this->propertyAccessor->getValue($item, 'id');
            }
        } catch (ExceptionInterface $e) {
            // Do nothing, return the raw value
        }

        return $value;
    }
}
