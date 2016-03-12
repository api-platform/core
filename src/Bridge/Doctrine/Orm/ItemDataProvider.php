<?php

/*
 * This file is part of the API Platform project.
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
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Item data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
class ItemDataProvider implements ItemDataProviderInterface
{
    private $managerRegistry;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $itemExtensions;
    private $decoratedProvider;

    /**
     * @param ManagerRegistry                        $managerRegistry
     * @param PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory
     * @param PropertyMetadataFactoryInterface       $propertyMetadataFactory
     * @param QueryItemExtensionInterface[]          $itemExtensions
     * @param ItemDataProviderInterface|null         $decoratedProvider
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory,
        PropertyMetadataFactoryInterface $propertyMetadataFactory,
        array $itemExtensions = [],
        ItemDataProviderInterface $decoratedProvider = null
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemExtensions = $itemExtensions;
        $this->decoratedProvider = $decoratedProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        if (null !== $this->decoratedProvider) {
            try {
                return $this->decoratedProvider->getItem($resourceClass, $id, $operationName, $fetchData);
            } catch (ResourceClassNotSupportedException $resourceClassNotSupportedException) {
                // Ignore it
            }
        }

        return $this->fallbackGetItem($resourceClass, $id, $operationName, $fetchData);
    }

    /**
     * @param string      $resourceClass
     * @param int|string  $id
     * @param string|null $operationName
     * @param bool        $fetchData
     *
     * @return object
     * @throws ResourceClassNotSupportedException
     */
    private function fallbackGetItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        $manager = $this->getManagerForClass($resourceClass);
        $identifiers = $this->getIdentifiers($resourceClass, $id);

        if (!$fetchData || $manager instanceof EntityManagerInterface) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $queryBuilder = $this->retrieveQueryBuilder($manager, $resourceClass);
        $this->applyIdentifiersToQueryBuilder($queryBuilder, $identifiers);

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $resourceClass, $identifiers, $operationName);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $resourceClass
     *
     * @return ObjectManager
     * @throws ResourceClassNotSupportedException
     */
    private function getManagerForClass(string $resourceClass)
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null !== $manager) {
            return $manager;
        }

        throw new ResourceClassNotSupportedException();
    }

    /**
     * @param string $resourceClass
     * @param int|string $id
     *
     * @return array Identifier values with keys as property names
     */
    private function getIdentifiers(string $resourceClass, $id)
    {
        $identifierValues = explode('-', $id);
        $identifiers = [];
        $i = 0;

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            $identifier = $propertyMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            if (!isset($identifierValues[$i])) {
                throw new InvalidArgumentException(sprintf('Invalid identifier "%s".', $id));
            }

            $identifiers[$propertyName] = $identifierValues[$i];
            ++$i;
        }

        return $identifiers;
    }

    /**
     * @param ObjectManager $resourceManager
     * @param string        $resourceClass
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function retrieveQueryBuilder(ObjectManager $resourceManager, string $resourceClass)
    {
        $repository = $resourceManager->getRepository($resourceClass);

        if ($repository instanceof  EntityRepository) {
            return $repository->createQueryBuilder('o');
        }

        if ($resourceManager instanceof EntityManagerInterface) {
            // TODO: check if $resourceClass is good here
            return $resourceManager->createQueryBuilder()
                ->select('o')
                ->from($resourceClass, 'o');
        }

        // TODO: handle this case
    }

    private function applyIdentifiersToQueryBuilder(QueryBuilder $queryBuilder, array $identifiers)
    {
        foreach ($identifiers as $propertyName => $value) {
            $placeholder = 'id_'.$propertyName;

            $queryBuilder
                ->where($queryBuilder->expr()->eq('o.'.$propertyName, ':'.$placeholder))
                ->setParameter($placeholder, $value);
        }
    }
}
