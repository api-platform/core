<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Api\ItemDataProviderInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Metadata\Property\Factory\CollectionMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\ItemMetadataFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Item data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class ItemDataProvider implements ItemDataProviderInterface
{
    private $managerRegistry;
    private $collectionMetadataFactory;
    private $itemMetadataFactory;
    private $itemExtensions;
    private $decorated;

    /**
     * @param ManagerRegistry                    $managerRegistry
     * @param CollectionMetadataFactoryInterface $collectionMetadataFactory
     * @param ItemMetadataFactoryInterface       $itemMetadataFactory
     * @param QueryItemExtensionInterface[]      $itemExtensions
     * @param ItemDataProviderInterface|null     $decorated
     */
    public function __construct(ManagerRegistry $managerRegistry, CollectionMetadataFactoryInterface $collectionMetadataFactory, ItemMetadataFactoryInterface $itemMetadataFactory, array $itemExtensions = [], ItemDataProviderInterface $decorated = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->collectionMetadataFactory = $collectionMetadataFactory;
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->itemExtensions = $itemExtensions;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        if ($this->decorated) {
            try {
                return $this->decorated->getItem($resourceClass, $id, $operationName, $fetchData);
            } catch (ResourceClassNotSupportedException $resourceClassNotSupportedException) {
                // Ignore it
            }
        }

        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException();
        }

        $identifierValues = explode('-', $id);
        $identifiers = [];
        $i = 0;

        foreach ($this->collectionMetadataFactory->create($resourceClass) as $propertyName) {
            $itemMetadata = $this->itemMetadataFactory->create($resourceClass, $propertyName);

            $identifier = $itemMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            if (!isset($identifierValues[$i])) {
                throw new InvalidArgumentException(sprintf('Invalid identifier "%s".', $id));
            }

            $identifiers[$propertyName] = $identifierValues[$i];
            ++$i;
        }

        if (!$fetchData || $manager instanceof EntityManagerInterface) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $repository = $manager->getRepository($resourceClass);
        $queryBuilder = $repository->createQueryBuilder('o');

        foreach ($identifiers as $propertyName => $value) {
            $placeholder = 'id_'.$propertyName;

            $queryBuilder
                ->where($queryBuilder->expr()->eq('o.'.$propertyName, ':'.$placeholder))
                ->setParameter($placeholder, $value)
            ;
        }

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $resourceClass, $identifiers, $operationName);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
