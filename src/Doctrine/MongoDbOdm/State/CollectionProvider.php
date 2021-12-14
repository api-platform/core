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

namespace ApiPlatform\Doctrine\MongoDbOdm\State;

use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\LinksHandlerTrait;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

class CollectionProvider implements ProviderInterface
{
    use LinksHandlerTrait;

    private $managerRegistry;
    private $resourceMetadataFactory;
    private $collectionExtensions;

    public function __construct(ManagerRegistry $managerRegistry, $resourceMetadataFactory, iterable $collectionExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
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
            $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName);
            $attribute = $operation->getExtraProperties()['doctrine_mongodb'] ?? [];
        } catch (OperationNotFoundException $e) {
            $attribute = $resourceMetadata->getOperation(null, true)->getExtraProperties()['doctrine_mongodb'] ?? [];
        }
        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        if (!$this->managerRegistry->getManagerForClass($resourceClass) instanceof DocumentManager) {
            return false;
        }

        $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);

        if ($operation instanceof GraphQlOperation) {
            return true;
        }

        return $operation->isCollection() ?? false;
    }
}
