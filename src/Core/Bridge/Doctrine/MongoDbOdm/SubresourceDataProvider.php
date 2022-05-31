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
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationCollectionExtensionInterface as LegacyAggregationCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationItemExtensionInterface as LegacyAggregationItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultCollectionExtensionInterface as LegacyAggregationResultCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\AggregationResultItemExtensionInterface as LegacyAggregationResultItemExtensionInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Doctrine\Odm\Extension\AggregationCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationItemExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Odm\Extension\AggregationResultItemExtensionInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

/**
 * Subresource data provider for the Doctrine MongoDB ODM.
 *
 * @experimental
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubresourceDataProvider implements SubresourceDataProviderInterface
{
    use IdentifierManagerTrait;

    private $managerRegistry;
    private $resourceMetadataFactory;
    private $collectionExtensions;
    private $itemExtensions;

    /**
     * @param LegacyAggregationCollectionExtensionInterface[]|AggregationCollectionExtensionInterface[] $collectionExtensions
     * @param LegacyAggregationItemExtensionInterface[]|AggregationItemExtensionInterface[]             $itemExtensions
     * @param ResourceMetadataCollectionFactoryInterface|ResourceMetadataFactoryInterface               $resourceMetadataFactory
     */
    public function __construct(ManagerRegistry $managerRegistry, $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, iterable $collectionExtensions = [], iterable $itemExtensions = [])
    {
        $this->managerRegistry = $managerRegistry;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
        $this->itemExtensions = $itemExtensions;
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function getSubresource(string $resourceClass, array $identifiers, array $context, string $operationName = null)
    {
        $manager = $this->managerRegistry->getManagerForClass($resourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new ResourceClassNotSupportedException(sprintf('The manager for "%s" must be an instance of "%s".', $resourceClass, DocumentManager::class));
        }

        $repository = $manager->getRepository($resourceClass);
        /** @var ObjectRepository $repository */
        if (!$repository instanceof DocumentRepository) {
            throw new RuntimeException(sprintf('The repository for "%s" must be an instance of "%s".', $resourceClass, DocumentRepository::class));
        }

        if (isset($context['identifiers'], $context['operation']) && !isset($context['property'])) {
            $context['property'] = $context['operation']->getExtraProperties()['legacy_subresource_property'] ?? null;
            $context['collection'] = $context['operation']->isCollection();
        }

        if (!isset($context['identifiers'], $context['property'])) {
            throw new ResourceClassNotSupportedException('The given resource class is not a subresource.');
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

        $aggregationBuilder = $this->buildAggregation($identifiers, $context, $executeOptions, $repository->createAggregationBuilder(), \count($context['identifiers']));

        if (true === $context['collection']) {
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
        } else {
            foreach ($this->itemExtensions as $extension) {
                if ($extension instanceof LegacyAggregationItemExtensionInterface) {
                    $extension->applyToItem($aggregationBuilder, $resourceClass, $identifiers, $operationName, $context);
                } elseif ($extension instanceof AggregationItemExtensionInterface) {
                    $extension->applyToItem($aggregationBuilder, $resourceClass, $identifiers, $context['operation'] ?? null, $context);
                }

                if ($extension instanceof LegacyAggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                    return $extension->getResult($aggregationBuilder, $resourceClass, $operationName, $context);
                }

                if ($extension instanceof AggregationResultItemExtensionInterface && $extension->supportsResult($resourceClass, $context['operation'] ?? null, $context)) {
                    return $extension->getResult($aggregationBuilder, $resourceClass, $context['operation'] ?? null, $context);
                }
            }
        }

        $iterator = $aggregationBuilder->hydrate($resourceClass)->execute($executeOptions);

        return $context['collection'] ? $iterator->toArray() : ($iterator->current() ?: null);
    }

    /**
     * @throws RuntimeException
     */
    private function buildAggregation(array $identifiers, array $context, array $executeOptions, Builder $previousAggregationBuilder, int $remainingIdentifiers, Builder $topAggregationBuilder = null): Builder
    {
        if ($remainingIdentifiers <= 0) {
            return $previousAggregationBuilder;
        }

        $topAggregationBuilder = $topAggregationBuilder ?? $previousAggregationBuilder;

        if (\is_string(key($context['identifiers']))) {
            $contextIdentifiers = array_keys($context['identifiers']);
            $identifier = $contextIdentifiers[$remainingIdentifiers - 1];
            $identifierResourceClass = $context['identifiers'][$identifier][0];
            $previousAssociationProperty = $contextIdentifiers[$remainingIdentifiers] ?? $context['property'];
        } else {
            @trigger_error('Identifiers should match the convention introduced in ADR 0001-resource-identifiers, this behavior will be removed in 3.0.', \E_USER_DEPRECATED);
            [$identifier, $identifierResourceClass] = $context['identifiers'][$remainingIdentifiers - 1];
            $previousAssociationProperty = $context['identifiers'][$remainingIdentifiers][0] ?? $context['property'];
        }

        $manager = $this->managerRegistry->getManagerForClass($identifierResourceClass);
        if (!$manager instanceof DocumentManager) {
            throw new RuntimeException(sprintf('The manager for "%s" must be an instance of "%s".', $identifierResourceClass, DocumentManager::class));
        }

        $classMetadata = $manager->getClassMetadata($identifierResourceClass);

        if (!$classMetadata instanceof ClassMetadata) {
            throw new RuntimeException(sprintf('The class metadata for "%s" must be an instance of "%s".', $identifierResourceClass, ClassMetadata::class));
        }

        $aggregation = $manager->createAggregationBuilder($identifierResourceClass);
        $normalizedIdentifiers = [];

        if (isset($identifiers[$identifier])) {
            // if it's an array it's already normalized, the IdentifierManagerTrait is deprecated
            if ($context[IdentifierConverterInterface::HAS_IDENTIFIER_CONVERTER] ?? false) {
                $normalizedIdentifiers = $identifiers[$identifier];
            } else {
                $normalizedIdentifiers = $this->normalizeIdentifiers($identifiers[$identifier], $manager, $identifierResourceClass);
            }
        }

        if ($classMetadata->hasAssociation($previousAssociationProperty)) {
            $aggregation->lookup($previousAssociationProperty)->alias($previousAssociationProperty);
            foreach ($normalizedIdentifiers as $key => $value) {
                $aggregation->match()->field($key)->equals($value);
            }
        } elseif ($classMetadata->isIdentifier($previousAssociationProperty)) {
            foreach ($normalizedIdentifiers as $key => $value) {
                $aggregation->match()->field($key)->equals($value);
            }

            return $aggregation;
        }

        // Recurse aggregations
        $aggregation = $this->buildAggregation($identifiers, $context, $executeOptions, $aggregation, --$remainingIdentifiers, $topAggregationBuilder);

        $results = $aggregation->execute($executeOptions)->toArray();
        $in = array_reduce($results, static function ($in, $result) use ($previousAssociationProperty) {
            return $in + array_map(static function ($result) {
                return $result['_id'];
            }, $result[$previousAssociationProperty] ?? []);
        }, []);
        $previousAggregationBuilder->match()->field('_id')->in($in);

        return $previousAggregationBuilder;
    }
}
