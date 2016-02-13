<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Dunglas\ApiBundle\Exception\ResourceClassNotSupportedException;
use Dunglas\ApiBundle\Metadata\Property\Factory\CollectionMetadataFactoryInterface;
use Dunglas\ApiBundle\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use Dunglas\ApiBundle\Api\DataProviderInterface;
use Dunglas\ApiBundle\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use Dunglas\ApiBundle\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use Dunglas\ApiBundle\Bridge\Doctrine\Orm\Extension\QueryResultExtensionInterface;

/**
 * Data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class DataProvider implements DataProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var CollectionMetadataFactoryInterface
     */
    private $collectionMetadataFactory;

    /**
     * @var ItemMetadataFactoryInterface
     */
    private $itemMetadataFactory;

    /**
     * @var QueryItemExtensionInterface[]
     */
    private $itemExtensions;

    /**
     * @var QueryCollectionExtensionInterface[]
     */
    private $collectionExtensions;

    /**
     * @var DataProviderInterface|null
     */
    private $decorated;

    /**
     * @param ManagerRegistry                     $managerRegistry
     * @param CollectionMetadataFactoryInterface  $collectionMetadataFactory
     * @param ItemMetadataFactoryInterface        $itemMetadataFactory
     * @param QueryCollectionExtensionInterface[] $collectionExtensions
     * @param QueryItemExtensionInterface[]       $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, CollectionMetadataFactoryInterface $collectionMetadataFactory, ItemMetadataFactoryInterface $itemMetadataFactory, array $collectionExtensions = [], array $itemExtensions = [], DataProviderInterface $decorated = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->collectionMetadataFactory = $collectionMetadataFactory;
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->itemExtensions = $itemExtensions;
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

        if (!$this->supports($resourceClass)) {
            throw new ResourceClassNotSupportedException();
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
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

    /**
     * Is this class supported?
     */
    private function supports(string $resourceClass) : bool
    {
        return null !== $this->managerRegistry->getManagerForClass($resourceClass);
    }
}
