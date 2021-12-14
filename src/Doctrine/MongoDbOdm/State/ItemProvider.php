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

use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\State\LinksHandlerTrait;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ItemProvider implements ProviderInterface
{
    use LinksHandlerTrait;

    private $resourceMetadataCollectionFactory;
    private $managerRegistry;
    private $itemExtensions;

    /**
     * @param AggregationItemExtensionInterface[] $itemExtensions
     */
    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ManagerRegistry $managerRegistry, iterable $itemExtensions = [])
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->managerRegistry = $managerRegistry;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \ApiPlatform\Exception\ResourceClassNotFoundException
     */
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        $fetchData = $context['fetch_data'] ?? true;
        if (!$fetchData) {
            return $manager->getReference($resourceClass, $identifiers);
        }

        $repository = $manager->getRepository($resourceClass);
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();

        foreach ($identifiers as $propertyName => $value) {
            $aggregationBuilder->match()->field($propertyName)->equals($value);
        }

        foreach ($this->itemExtensions as $extension) {
            $extension->applyToItem($aggregationBuilder, $resourceClass, $identifiers, $operationName, $context);

            if ($extension instanceof AggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
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

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions)->current() ?: null;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        if (!$this->managerRegistry->getManagerForClass($resourceClass) instanceof EntityManagerInterface) {
            return false;
        }

        $operation = $context['operation'] ?? $this->resourceMetadataCollectionFactory->create($resourceClass)->getOperation($operationName);

        return !($operation->isCollection() ?? false);
    }
}
