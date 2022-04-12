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

namespace ApiPlatform\Doctrine\Odm\Extension;

use ApiPlatform\Api\FilterLocatorTrait;
use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Container\ContainerInterface;

/**
 * Applies filters on a resource aggregation.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements AggregationCollectionExtensionInterface
{
    use FilterLocatorTrait;

    private $resourceMetadataFactory;

    /**
     * @param ContainerInterface|FilterCollection $filterLocator           The new filter locator or the deprecated filter collection
     * @param mixed                               $resourceMetadataFactory
     */
    public function __construct($resourceMetadataFactory, $filterLocator)
    {
        $this->setFilterLocator($filterLocator);

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, string $operationName = null, array &$context = [])
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        try {
            $operation = $resourceMetadata->getOperation($operationName);
            $resourceFilters = $operation->getFilters();
        } catch (OperationNotFoundException $e) {
            $resourceFilters = $resourceMetadata->getOperation(null, true)->getFilters();
        }

        if (empty($resourceFilters)) {
            return;
        }

        foreach ($resourceFilters as $filterId) {
            $filter = $this->getFilter($filterId);
            if ($filter instanceof FilterInterface) {
                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($aggregationBuilder, $resourceClass, $operationName, $context);
            }
        }
    }
}

class_alias(FilterExtension::class, \ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Extension\FilterExtension::class);
