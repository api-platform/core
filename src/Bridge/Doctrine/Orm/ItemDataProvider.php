<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryResultItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
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
     *
     * The context may contain a `fetch_data` key representing whether the value should be fetched by Doctrine or if we should return a reference.
     *
     * @throws RuntimeException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (null === $manager) {
            throw new ResourceClassNotSupportedException();
        }

        $identifiers = $this->normalizeIdentifiers($id, $manager, $resourceClass);

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData && $manager instanceof EntityManagerInterface) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createQueryBuilder')) {
            throw new RuntimeException('The repository class must have a "createQueryBuilder" method.');
        }

        $queryBuilder = $repository->createQueryBuilder('o');
        $queryNameGenerator = new QueryNameGenerator();

        $this->addWhereForIdentifiers($identifiers, $queryBuilder);

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);

            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName)) {
                return $extension->getResult($queryBuilder);
            }
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
     * @param int|string    $id
     * @param ObjectManager $manager
     * @param string        $resourceClass
     *
     * @throws PropertyNotFoundException
     *
     * @return array
     */
    private function normalizeIdentifiers($id, ObjectManager $manager, string $resourceClass): array
    {
        $identifierValues = [$id];
        $doctrineMetadataIdentifier = $manager->getClassMetadata($resourceClass)->getIdentifier();

        if (count($doctrineMetadataIdentifier) >= 2) {
            $identifiers = explode(';', $id);
            $identifiersMap = [];

            // first transform identifiers to a proper key/value array
            foreach ($identifiers as $identifier) {
                $keyValue = explode('=', $identifier);
                $identifiersMap[$keyValue[0]] = $keyValue[1];
            }
        }

        $identifiers = [];
        $i = 0;

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

            $identifier = $propertyMetadata->isIdentifier();
            if (null === $identifier || false === $identifier) {
                continue;
            }

            $identifier = !isset($identifiersMap) ? $identifierValues[$i] ?? null : $identifiersMap[$propertyName] ?? null;

            if (null === $identifier) {
                throw new PropertyNotFoundException(sprintf('Invalid identifier "%s", "%s" has not been found.', $id, $propertyName));
            }

            $identifiers[$propertyName] = $identifier;
            ++$i;
        }

        return $identifiers;
    }
}
