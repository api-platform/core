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

use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Doctrine\Common\ParameterExtensionTrait;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface; // Explicitly import PropertyAwareFilterInterface
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Reads operation parameters and execute its filter.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class ParameterExtension implements AggregationCollectionExtensionInterface, AggregationItemExtensionInterface
{
    use ParameterExtensionTrait;

    public function __construct(
        ContainerInterface $filterLocator,
        ?ManagerRegistry $managerRegistry = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->filterLocator = $filterLocator;
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
    }

    /**
     * @param array<string, mixed> $context
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

            if (!$filter instanceof FilterInterface) {
                continue;
            }

            $this->configureFilter($filter, $parameter);

            $context['filters'] = $values;
            $context['parameter'] = $parameter;

            $filter->apply($aggregationBuilder, $resourceClass, $operation, $context);

            unset($context['filters'], $context['parameter']);
        }

        if (isset($context['match'])) {
            $aggregationBuilder->match()->addAnd($context['match']);
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
