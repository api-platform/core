<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\MongoDB\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Bridge\Doctrine\MongoDB\Filter\FilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * Applies filters on a resource query.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements QueryCollectionExtensionInterface
{
    private $resourceMetadataFactory;
    private $filters;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, FilterCollection $filters)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceFilters = $resourceMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

        if (empty($resourceFilters)) {
            return;
        }

        foreach ($this->filters as $filterName => $filter) {
            if (in_array($filterName, $resourceFilters) && $filter instanceof FilterInterface) {
                $filter->apply($queryBuilder, $resourceClass, $operationName);
            }
        }
    }
}
