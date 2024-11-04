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

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\ParameterValueExtractorTrait;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Reads operation parameters and execute its filter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ParameterExtension implements AggregationCollectionExtensionInterface, AggregationItemExtensionInterface
{
    use ParameterValueExtractorTrait;

    public function __construct(
        private readonly ContainerInterface $filterLocator,
        private readonly ?ManagerRegistry $managerRegistry = null,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function applyFilter(Builder $aggregationBuilder, ?string $resourceClass = null, ?Operation $operation = null, array &$context = []): void
    {
        foreach ($operation->getParameters() ?? [] as $parameter) {
            if (null === ($v = $parameter->getValue()) || $v instanceof ParameterNotFound) {
                continue;
            }

            $values = $this->extractParameterValue($parameter, $v);
            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $filter = match (true) {
                $filterId instanceof FilterInterface => $filterId,
                \is_string($filterId) && $this->filterLocator->has($filterId) => $this->filterLocator->get($filterId),
                default => null,
            };

            if (!($filter instanceof FilterInterface)) {
                throw new InvalidArgumentException(\sprintf('Could not find filter "%s" for parameter "%s" in operation "%s" for resource "%s".', $filterId, $parameter->getKey(), $operation?->getShortName(), $resourceClass));
            }

            if ($filter instanceof ManagerRegistryAwareInterface && !$filter->hasManagerRegistry()) {
                $filter->setManagerRegistry($this->managerRegistry);
            }

            if ($filter instanceof AbstractFilter && !$filter->getProperties()) {
                $filter->setProperties([$parameter->getProperty() ?? $parameter->getKey() => []]);
            }

            $filterContext = ['filters' => $values, 'parameter' => $parameter];
            $filter->apply($aggregationBuilder, $resourceClass, $operation, $filterContext);
            // update by reference
            if (isset($filterContext['mongodb_odm_sort_fields'])) {
                $context['mongodb_odm_sort_fields'] = $filterContext['mongodb_odm_sort_fields'];
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
