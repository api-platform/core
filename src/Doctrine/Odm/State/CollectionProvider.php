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

use ApiPlatform\Doctrine\Common\State\LinksHandlerLocatorTrait;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;

/**
 * Collection state provider using the Doctrine ODM.
 */
final class CollectionProvider implements ProviderInterface
{
    use LinksHandlerLocatorTrait;
    use LinksHandlerTrait;

    /**
     * @param AggregationCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ManagerRegistry $managerRegistry, private readonly iterable $collectionExtensions = [], ?ContainerInterface $handleLinksLocator = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->handleLinksLocator = $handleLinksLocator;
        $this->managerRegistry = $managerRegistry;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $documentClass = $operation->getClass();
        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getDocumentClass()) {
            $documentClass = $options->getDocumentClass();
        }

        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($documentClass);

        $repository = $manager->getRepository($documentClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $documentClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();

        if ($handleLinks = $this->getLinksHandler($operation)) {
            $handleLinks($aggregationBuilder, $uriVariables, ['documentClass' => $documentClass, 'operation' => $operation] + $context);
        } else {
            $this->handleLinks($aggregationBuilder, $uriVariables, $context, $documentClass, $operation);
        }

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($aggregationBuilder, $documentClass, $operation, $context);

            if ($extension instanceof AggregationResultCollectionExtensionInterface && $extension->supportsResult($documentClass, $operation, $context)) {
                return $extension->getResult($aggregationBuilder, $documentClass, $operation, $context);
            }
        }

        $attribute = $operation->getExtraProperties()['doctrine_mongodb'] ?? [];
        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($documentClass)->execute($executeOptions);
    }
}
