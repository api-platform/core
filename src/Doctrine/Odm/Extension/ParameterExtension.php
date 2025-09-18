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

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\ParameterValueExtractorTrait;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
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
    use ParameterValueExtractorTrait;

    public function __construct(
        private readonly ContainerInterface $filterLocator,
        private readonly ?ManagerRegistry $managerRegistry = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
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

            if ($this->managerRegistry && $filter instanceof ManagerRegistryAwareInterface && !$filter->hasManagerRegistry()) {
                $filter->setManagerRegistry($this->managerRegistry);
            }

            if ($this->logger && $filter instanceof LoggerAwareInterface && !$filter->hasLogger()) {
                $filter->setLogger($this->logger);
            }

            if ($filter instanceof AbstractFilter && !$filter->getProperties()) {
                $propertyKey = $parameter->getProperty() ?? $parameter->getKey();

                if (str_contains($propertyKey, ':property')) {
                    $extraProperties = $parameter->getExtraProperties()['_properties'] ?? [];
                    foreach (array_keys($extraProperties) as $property) {
                        $properties[$property] = $parameter->getFilterContext();
                    }
                } else {
                    $properties = [$propertyKey => $parameter->getFilterContext()];
                }

                $filter->setProperties($properties ?? []);
            }

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
