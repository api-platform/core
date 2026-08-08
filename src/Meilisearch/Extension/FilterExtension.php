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

namespace ApiPlatform\Meilisearch\Extension;

use ApiPlatform\Meilisearch\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Psr\Container\ContainerInterface;

/**
 * Resolves the operation's `#[ApiFilter]`-declared filter services and lets
 * each contribute to the search parameters, then AND-joins every collected
 * `filterExpressions` fragment into Meilisearch's single `filter` string.
 *
 * Simpler than Elasticsearch's AbstractFilterExtension: there's one filter
 * interface (not a family split by ConstantScoreFilterInterface/
 * SortFilterInterface), because there's no JSON query-DSL tree to merge
 * fragments into at different points.
 */
final class FilterExtension implements RequestParametersCollectionExtensionInterface
{
    public function __construct(private readonly ContainerInterface $filterLocator)
    {
    }

    public function applyToCollection(array $parameters, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $filterIds = $operation?->getFilters();

        if ($filterIds) {
            foreach ($filterIds as $filterId) {
                if ($this->filterLocator->has($filterId) && ($filter = $this->filterLocator->get($filterId)) instanceof FilterInterface) {
                    $parameters = $filter->apply($parameters, $resourceClass, $operation, $context);
                }
            }
        }

        if ($expressions = $parameters['filterExpressions'] ?? null) {
            $parameters['filter'] = implode(' AND ', $expressions);
        }
        unset($parameters['filterExpressions']);

        return $parameters;
    }
}
