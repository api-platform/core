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
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\HeaderParameterInterface;
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
        if (!($request = $context['request'] ?? null)) {
            return;
        }

        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        foreach ($operation->getParameters() ?? [] as $parameter) {
            $key = $parameter->getKey();
            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $parameters = $parameter instanceof HeaderParameterInterface ? $request->attributes->get('_api_header_parameters') : $request->attributes->get('_api_query_parameters');
            $parsedKey = explode('[:property]', $key);
            if (isset($parsedKey[0]) && isset($parameters[$parsedKey[0]])) {
                $key = $parsedKey[0];
            }

            if (!isset($parameters[$key])) {
                continue;
            }

            $value = $parameters[$key];
            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
            if ($filter instanceof FilterInterface) {
                $filterContext = ['filters' => [$key => $value]] + $context;
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
