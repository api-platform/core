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
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Collection state provider using the Doctrine ODM.
 */
final class CollectionProvider implements ProviderInterface
{
    use LinksHandlerTrait;

    /**
     * @param AggregationCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, private readonly ManagerRegistry $managerRegistry, private readonly iterable $collectionExtensions = [])
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $resourceClass = $operation->getClass();
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();

        $this->handleLinks($aggregationBuilder, $uriVariables, $context, $resourceClass, $operation);

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($aggregationBuilder, $resourceClass, $operation, $context);

            if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operation, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operation, $context);
            }
        }

        $attribute = $operation->getExtraProperties()['doctrine_mongodb'] ?? [];
        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions);
    }
}
