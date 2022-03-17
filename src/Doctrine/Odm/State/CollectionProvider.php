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

namespace ApiPlatform\Doctrine\Odm\State;

use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

/**
 * Collection state provider using the Doctrine ODM.
 */
final class CollectionProvider implements ProviderInterface
{
    use LinksHandlerTrait;

    private $resourceMetadataCollectionFactory;
    private $managerRegistry;
    private $collectionExtensions;

    /**
     * @param AggregationCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ManagerRegistry $managerRegistry, iterable $collectionExtensions = [])
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->managerRegistry = $managerRegistry;
        $this->collectionExtensions = $collectionExtensions;
    }

    public function provide(string $resourceClass, array $uriVariables = [], ?string $operationName = null, array $context = [])
    {
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        /** @var ObjectRepository $repository */
        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();

        $this->handleLinks($aggregationBuilder, $uriVariables, $context, $resourceClass, $operationName);

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($aggregationBuilder, $resourceClass, $operationName, $context);

            if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
            }
        }

        $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
        try {
            $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName);
            $attribute = $operation->getExtraProperties()['doctrine_mongodb'] ?? [];
        } catch (OperationNotFoundException $e) {
            $attribute = $resourceMetadata->getOperation(null, true)->getExtraProperties()['doctrine_mongodb'] ?? [];
        }
        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions);
    }

    public function supports(string $resourceClass, array $uriVariables = [], ?string $operationName = null, array $context = []): bool
    {
        if (!$this->managerRegistry->getManagerForClass($resourceClass) instanceof DocumentManager) {
            return false;
        }

        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);

        if ($operation instanceof GraphQlOperation) {
            return true;
        }

        return $operation->isCollection() ?? false;
    }
}
