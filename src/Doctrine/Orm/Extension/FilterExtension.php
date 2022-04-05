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

namespace ApiPlatform\Doctrine\Orm\Extension;

use ApiPlatform\Api\FilterLocatorTrait;
use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;

/**
 * Applies filters on a resource query.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements ContextAwareQueryCollectionExtensionInterface
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
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, string $operationName = null, array $context = [])
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        /** @var ResourceMetadata|ResourceMetadataCollection */
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

        if ($resourceMetadata instanceof ResourceMetadata) {
            $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);
        } else {
            try {
                $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName);
                $resourceFilters = $operation->getFilters();
            } catch (OperationNotFoundException $e) {
                // In some cases the operation may not exist
                if (isset($context['graphql_operation_name'])) {
                    $resourceFilters = $resourceMetadata->getOperation(null, true)->getFilters();
                }
            }
        }

        if (empty($resourceFilters)) {
            return;
        }

        $orderFilters = [];

        foreach ($resourceFilters as $filterId) {
            $filter = $this->getFilter($filterId);
            if ($filter instanceof FilterInterface) {
                // Apply the OrderFilter after every other filter to avoid an edge case where OrderFilter would do a LEFT JOIN instead of an INNER JOIN
                if ($filter instanceof OrderFilter) {
                    $orderFilters[] = $filter;
                    continue;
                }

                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
            }
        }

        foreach ($orderFilters as $orderFilter) {
            $context['filters'] = $context['filters'] ?? [];
            $orderFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operationName, $context);
        }
    }
}

class_alias(FilterExtension::class, \ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\FilterExtension::class);
