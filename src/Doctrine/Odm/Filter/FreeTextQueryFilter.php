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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class FreeTextQueryFilter implements FilterInterface, ManagerRegistryAwareInterface, LoggerAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use LoggerAwareTrait;
    use ManagerRegistryAwareTrait;

    /**
     * @param FilterInterface|array<string, FilterInterface> $filter     a filter applied to every property,
     *                                                                   or a map of `property => filter` to use a
     *                                                                   dedicated filter per property
     * @param list<string>|null                              $properties an array of properties, defaults to
     *                                                                   the map keys when `$filter` is a map,
     *                                                                   otherwise to `parameter->getProperties()`
     */
    public function __construct(private readonly FilterInterface|array $filter, private readonly ?array $properties = null)
    {
    }

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $filterMap = \is_array($this->filter) ? $this->filter : null;

        if (null === $filterMap) {
            if ($this->filter instanceof ManagerRegistryAwareInterface) {
                $this->filter->setManagerRegistry($this->getManagerRegistry());
            }

            if ($this->filter instanceof LoggerAwareInterface) {
                $this->filter->setLogger($this->getLogger());
            }
        }

        $parameter = $context['parameter'];
        $properties = $this->properties ?? (null !== $filterMap ? array_keys($filterMap) : $parameter->getProperties()) ?? [];

        foreach ($properties as $property) {
            $filter = null !== $filterMap ? ($filterMap[$property] ?? null) : $this->filter;

            if (null === $filter) {
                continue;
            }

            if (null !== $filterMap) {
                if ($filter instanceof ManagerRegistryAwareInterface) {
                    $filter->setManagerRegistry($this->getManagerRegistry());
                }

                if ($filter instanceof LoggerAwareInterface) {
                    $filter->setLogger($this->getLogger());
                }
            }

            $subParameter = $parameter->withProperty($property);

            $nestedPropertiesInfo = $parameter->getExtraProperties()['nested_properties_info'] ?? [];
            $subParameter = $subParameter->withExtraProperties([
                ...$subParameter->getExtraProperties(),
                'nested_properties_info' => isset($nestedPropertiesInfo[$property])
                    ? [$property => $nestedPropertiesInfo[$property]]
                    : [],
            ]);

            $newContext = ['parameter' => $subParameter, 'match' => $context['match'] ?? $aggregationBuilder->match()->expr()] + $context;
            $filter->apply(
                $aggregationBuilder,
                $resourceClass,
                $operation,
                $newContext,
            );

            if (isset($newContext['match'])) {
                $context['match'] = $newContext['match'];
            }
        }
    }
}
