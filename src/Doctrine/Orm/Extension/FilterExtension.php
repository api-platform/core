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

use ApiPlatform\Doctrine\Orm\Filter\FilterInterface;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Psr\Container\ContainerInterface;

/**
 * Applies filters on a resource query.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Samuel ROZE <samuel.roze@gmail.com>
 */
final class FilterExtension implements QueryCollectionExtensionInterface
{
    /** @var ContainerInterface */
    private $filterLocator;

    public function __construct(ContainerInterface $filterLocator)
    {
        $this->filterLocator = $filterLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass = null, Operation $operation = null, array $context = []): void
    {
        if (null === $resourceClass) {
            throw new InvalidArgumentException('The "$resourceClass" parameter must not be null');
        }

        $resourceFilters = $operation ? $operation->getFilters() : [];

        if (empty($resourceFilters)) {
            return;
        }

        $orderFilters = [];

        foreach ($resourceFilters as $filterId) {
            $filter = $this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null;
            if ($filter instanceof FilterInterface) {
                // Apply the OrderFilter after every other filter to avoid an edge case where OrderFilter would do a LEFT JOIN instead of an INNER JOIN
                if ($filter instanceof OrderFilter) {
                    $orderFilters[] = $filter;
                    continue;
                }

                $context['filters'] = $context['filters'] ?? [];
                $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
            }
        }

        foreach ($orderFilters as $orderFilter) {
            $context['filters'] = $context['filters'] ?? [];
            $orderFilter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }
}
