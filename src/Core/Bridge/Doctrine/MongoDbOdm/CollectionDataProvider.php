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

use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Exception\OperationNotFoundException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * @param AggregationCollectionExtensionInterface[] $collectionExtensions
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
    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();
        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($aggregationBuilder, $resourceClass, $operationName, $context);

            if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
            }
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        try {
            $operation = $context['operation'] ?? (isset($context['graphql_operation_name']) ? $resourceMetadata->getGraphQlOperation($operationName) : $resourceMetadata->getOperation($operationName));
            $attribute = $operation->getExtraProperties()['doctrine_mongodb'] ?? [];
        } catch (OperationNotFoundException $e) {
            $attribute = $resourceMetadata->getOperation(null, true)->getExtraProperties()['doctrine_mongodb'] ?? [];
        }
        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions);
    }
}
