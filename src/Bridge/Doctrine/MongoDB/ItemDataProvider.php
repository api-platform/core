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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB;

use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Item data provider for the Doctrine MongoDB ODM.
 */
class ItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private $managerRegistry;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $itemExtensions;

    /**
     * @param QueryItemExtensionInterface[] $itemExtensions
     */
    public function __construct(ManagerRegistry $managerRegistry, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, /* iterable */ $itemExtensions = [], ItemDataProviderInterface $decorated = null)
    {
        $this->managerRegistry = $managerRegistry;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemExtensions = $itemExtensions;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return null !== $this->managerRegistry->getManagerForClass($resourceClass);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $identifierValues = explode('-', (string) $id);
        $identifiers = [];
        $i = 0;

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $propertyName) {
            $itemMetadata = $this->propertyMetadataFactory->create($resourceClass, $propertyName);

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

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData && $manager instanceof DocumentManager) {
            return $manager->getReference($resourceClass, reset($identifiers));
        }

        $repository = $manager->getRepository($resourceClass);
        if (!method_exists($repository, 'createAggregationBuilder')) {
            throw new RuntimeException('The repository class must have a "createAggregationBuilder" method.');
        }
        /** @var Builder $aggregationBuilder */
        $aggregationBuilder = $repository->createAggregationBuilder();
        $queryNameGenerator = new QueryNameGenerator();

        foreach ($identifiers as $propertyName => $value) {
            $aggregationBuilder->match()->field($propertyName)->equals($value);
        }

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($aggregationBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName);
        }

        return $aggregationBuilder->hydrate($resourceClass)->execute()->getSingleResult();
    }
}
