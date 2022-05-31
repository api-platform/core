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

use ApiPlatform\Core\Bridge\Doctrine\Common\Util\IdentifierManagerTrait;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface as LegacyAggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface as LegacyAggregationResultItemExtensionInterface;
use ApiPlatform\Core\DataProvider\DenormalizedIdentifiersAwareItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

/**
 * Item data provider for the Doctrine MongoDB ODM.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class ItemDataProvider implements DenormalizedIdentifiersAwareItemDataProviderInterface, RestrictedDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $resourceMetadataFactory;
    private $itemExtensions;

    /**
     * @param LegacyAggregationItemExtensionInterface[]|AggregationItemExtensionInterface[] $itemExtensions
     * @param ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface   $resourceMetadataFactory
     */
    public function __construct(ManagerRegistry $managerRegistry, $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->itemExtensions = $itemExtensions;
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
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        /** @var DocumentManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);

        if (!\is_array($id) && !($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false)) {
            $id = $this->normalizeIdentifiers($id, $manager, $resourceClass);
        }

        $id = (array) $id;

        if (!($context['fetch_data'] ?? true)) {
            return $manager->getReference($resourceClass, reset($id));
        }

        $repository = $manager->getRepository($resourceClass);
        /** @var ObjectRepository $repository */
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        $aggregationBuilder = $repository->createAggregationBuilder();

        foreach ($id as $propertyName => $value) {
            $aggregationBuilder->match()->field($propertyName)->equals($value);
        }

        foreach ($this->itemExtensions as $extension) {
            if ($extension instanceof LegacyAggregationItemExtensionInterface) {
                $extension->applyToItem($aggregationBuilder, $resourceClass, $id, $operationName, $context);
            } elseif ($extension instanceof AggregationItemExtensionInterface) {
                $extension->applyToItem($aggregationBuilder, $resourceClass, $id, $context['operation'] ?? null, $context);
            }

            if ($extension instanceof LegacyAggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
            }

            if ($extension instanceof AggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $context['operation'] ?? null, $context)) {
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
            $attribute = $resourceMetadata->getItemOperationAttribute($operationName, 'doctrine_mongodb', [], true);
        }

        $executeOptions = $attribute['execute_options'] ?? [];

        return $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions)->current() ?: null;
    }
}
