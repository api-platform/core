<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Doctrine\Orm\Extension;

use ApiPlatform\Core\Api\FilterCollection;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies filters on a resource query.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements QueryCollectionExtensionInterface
{
    private $itemMetadataFactory;
    private $filters;

    public function __construct(ItemMetadataFactoryInterface $itemMetadataFactory, FilterCollection $filters)
    {
        $this->itemMetadataFactory = $itemMetadataFactory;
        $this->filters = $filters;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, string $resourceClass, string $operationName = null)
    {
        $itemMetadata = $this->itemMetadataFactory->create($resourceClass);
        $resourceFilters = $itemMetadata->getCollectionOperationAttribute($operationName, 'filters', [], true);

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
