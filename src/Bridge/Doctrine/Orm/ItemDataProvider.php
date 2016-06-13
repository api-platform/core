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

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Item data provider for the Doctrine ORM.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
class ItemDataProvider implements ItemDataProviderInterface
{
    private $managerRegistry;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $itemExtensions;

    /**
     * @param ManagerRegistry                        $managerRegistry
     * @param PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory
     * @param PropertyMetadataFactoryInterface       $propertyMetadataFactory
     * @param QueryItemExtensionInterface[]          $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, array $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException();
        }

        $identifiers = $this->normalizeIdentifiers($id, $manager, $resourceClass);

        if (!$fetchData && $manager instanceof EntityManagerInterface) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $repository = $manager->getRepository($resourceClass);
        $queryBuilder = $repository->createQueryBuilder('o');

        $this->addWhereForIdentifiers($identifiers, $queryBuilder);

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $resourceClass, $identifiers, $operationName);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Add WHERE conditions to the query for one or more identifiers (simple or composite).
     *
     * @param array        $identifiers
     * @param QueryBuilder $queryBuilder
     */
    private function addWhereForIdentifiers(array $identifiers, QueryBuilder $queryBuilder)
    {
        if (empty($identifiers)) {
            return;
        }

        foreach ($identifiers as $identifier => $value) {
            $placeholder = ':id_'.$identifier;
            $expression = $queryBuilder->expr()->eq(
                'o.'.$identifier,
                $placeholder
            );

            $queryBuilder->andWhere($expression);

            $queryBuilder->setParameter($placeholder, $value);
        }
    }

    /**
     * Transform and check the identifier, composite or not.
     *
     * @param $id
     * @param $manager
     * @param $resourceClass
     *
     * @return array
     */
    private function normalizeIdentifiers($id, $manager, $resourceClass) : array
    {
        $doctrineMetadataIdentifier = $manager->getClassMetadata($resourceClass)->getIdentifier();
        $identifierValues = [$id];

        if (count($doctrineMetadataIdentifier) >= 2) {
            $identifiers = explode(';', $id);
            $identifiersMap = [];

            // first transform identifiers to a proper key/value array
            foreach ($identifiers as $identifier) {
                $keyValue = explode('=', $identifier);
                $identifiersMap[$keyValue[0]] = $keyValue[1];
            }

            $identifierValuesArray = [];

            // then, loop through doctrine metadata identifiers to keep the same identifiers order
            foreach ($doctrineMetadataIdentifier as $metadataIdentifierKey) {
                $identifierValuesArray[] = $identifiersMap[$metadataIdentifierKey];
            }

            $identifierValues = $identifierValuesArray;
        }

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
}
