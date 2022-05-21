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

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm;

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface as LegacyAggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface as LegacyAggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

/**
 * Collection data provider for the Doctrine MongoDB ODM.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class CollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $managerRegistry;
    private $resourceMetadataFactory;
    private $collectionExtensions;

    /**
     * @param LegacyAggregationCollectionExtensionInterface[]|AggregationCollectionExtensionInterface[] $collectionExtensions
     * @param ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface               $resourceMetadataFactory
     */
    public function __construct(ManagerRegistry $managerRegistry, $resourceMetadataFactory, iterable $collectionExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
    }

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return $this->managerRegistry->getManagerForClass($resourceClass) instanceof DocumentManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getCollection(string $resourceClass, string $operationName = null, array $context = []): iterable
    {
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        /** @var ObjectRepository $repository */
        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();
        foreach ($this->collectionExtensions as $extension) {
            if ($extension instanceof LegacyAggregationCollectionExtensionInterface) {
                $extension->applyToCollection($aggregationBuilder, $resourceClass, $operationName, $context);
            } elseif ($extension instanceof AggregationCollectionExtensionInterface) {
                $extension->applyToCollection($aggregationBuilder, $resourceClass, $context['operation'] ?? null, $context);
            }

            if ($extension instanceof LegacyAggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
            }

            if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $context['operation'] ?? null, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $context['operation'] ?? null, $context);
            }
        }

        $attribute = [];
        if ($this->resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            try {
                $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName);
                $attribute = $operation->getExtraProperties()['doctrine_mongodb'] ?? [];
            } catch (OperationNotFoundException $e) {
                $attribute = $resourceMetadata->getOperation()->getExtraProperties()['doctrine_mongodb'] ?? [];
            }
        } else {
            /** @var ResourceMetadata */
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $attribute = $resourceMetadata->getSubresourceOperationAttribute($operationName, 'doctrine_mongodb', [], true);
        }

        $executeOptions = $attribute['execute_options'] ?? [];
        $builder = $aggregationBuilder->hydrate($resourceClass);

        if (method_exists($builder, 'getAggregation')) {
            return $builder->getAggregation($executeOptions)->getIterator();
        }

        return $builder->execute($executeOptions);
    }
}
