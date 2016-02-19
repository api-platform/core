<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Bridge\Doctrine\Orm;

use ApiPlatform\Builder\Api\CollectionDataProviderInterface;
use ApiPlatform\Builder\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Builder\Bridge\Doctrine\Orm\Extension\QueryResultExtensionInterface;
use ApiPlatform\Builder\Exception\ResourceClassNotSupportedException;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Collection data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class CollectionDataProvider implements CollectionDataProviderInterface
{
    private $managerRegistry;
    private $collectionExtensions;
    private $decorated;

    /**
     * @param ManagerRegistry                      $managerRegistry
     * @param QueryCollectionExtensionInterface[]  $collectionExtensions
     * @param CollectionDataProviderInterface|null $decorated
     */
    public function __construct(ManagerRegistry $managerRegistry, array $collectionExtensions = [], CollectionDataProviderInterface $decorated = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, string $operationName = null)
    {
        if ($this->decorated) {
            try {
                return $this->decorated->getCollection($resourceClass, $operationName);
            } catch (ResourceClassNotSupportedException $resourceClassNotSupportedException) {
                // Ignore it
            }
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException();
        }

        $repository = $manager->getRepository($resourceClass);
        $queryBuilder = $repository->createQueryBuilder('o');

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($queryBuilder, $resourceClass, $operationName);

            if ($extension instanceof QueryResultExtensionInterface) {
                if ($extension->supportsResult($resourceClass, $operationName)) {
                    return $extension->getResult($queryBuilder);
                }
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
