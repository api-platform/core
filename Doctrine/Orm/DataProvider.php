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
use Dunglas\ApiBundle\Api\ResourceInterface;
use Dunglas\ApiBundle\Model\DataProviderInterface;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class DataProvider implements DataProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var QueryItemExtensionInterface[]
     */
    private $itemExtensions;

    /**
     * @var QueryCollectionExtensionInterface[]
     */
    private $collectionExtensions;

    /**
     * @param ManagerRegistry                     $managerRegistry
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     * @param QueryItemExtensionInterface[]       $itemExtensions
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        array $collectionExtensions = [],
        array $itemExtensions = []
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->itemExtensions = $itemExtensions;
        $this->collectionExtensions = $collectionExtensions;
    }

    /**
     * @param QueryItemExtensionInterface $extension
     */
    public function addItemExtension(QueryItemExtensionInterface $extension)
    {
        $this->itemExtensions[] = $extension;
    }

    /**
     * @param QueryCollectionExtensionInterface $extension
     */
    public function addCollectionExtension(QueryCollectionExtensionInterface $extension)
    {
        $this->collectionExtensions[] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false)
    {
        $entityClass = $resource->getEntityClass();
        $manager = $this->managerRegistry->getManagerForClass($entityClass);

        if ($fetchData || !method_exists($manager, 'getReference')) {
            $repository = $manager->getRepository($entityClass);
            $queryBuilder = $repository->createQueryBuilder('o');
            $identifier = $manager->getClassMetadata($resource->getEntityClass())->getIdentifierFieldNames()[0];
            $queryBuilder->where($queryBuilder->expr()->eq('o.'.$identifier, ':id'))->setParameter('id', $id);

            foreach ($this->itemExtensions as $extension) {
                $extension->applyToItem($resource, $queryBuilder, $id);
            }

            return $queryBuilder->getQuery()->getOneOrNullResult();
        }

        return $manager->getReference($entityClass, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResourceInterface $resource)
    {
        $entityClass = $resource->getEntityClass();

        $manager = $this->managerRegistry->getManagerForClass($resource->getEntityClass());
        $repository = $manager->getRepository($entityClass);
        $queryBuilder = $repository->createQueryBuilder('o');

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($resource, $queryBuilder);

            if ($extension instanceof QueryResultExtensionInterface) {
                if ($extension->supportsResult($resource)) {
                    return $extension->getResult($queryBuilder);
                }
            }
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
