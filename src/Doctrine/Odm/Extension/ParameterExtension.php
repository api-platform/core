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

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Container\ContainerInterface;

/**
 * Reads operation parameters and execute its filter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ParameterExtension implements AggregationCollectionExtensionInterface, AggregationItemExtensionInterface
{
    public function __construct(private readonly ContainerInterface $filterLocator)
    {
    }

    private function applyFilter(Builder $aggregationBuilder, ?string $resourceClass = null, ?Operation $operation = null, array &$context = []): void
    {
        foreach ($operation->getParameters() ?? [] as $parameter) {
            $values = $parameter->getExtraProperties()['_api_values'] ?? [];
            if (!$values) {
                continue;
            }

            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
            if ($filter instanceof FilterInterface) {
                $filterContext = ['filters' => $values];
                $filter->apply($aggregationBuilder, $resourceClass, $operation, $filterContext);
                // update by reference
                if (isset($filterContext['mongodb_odm_sort_fields'])) {
                    $context['mongodb_odm_sort_fields'] = $filterContext['mongodb_odm_sort_fields'];
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $this->applyFilter($aggregationBuilder, $resourceClass, $operation, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function applyToItem(Builder $aggregationBuilder, string $resourceClass, array $identifiers, ?Operation $operation = null, array &$context = []): void
    {
        $this->applyFilter($aggregationBuilder, $resourceClass, $operation, $context);
    }
}
