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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\LoggerAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies several filters to a single parameter; each filter self-selects by the value shape it supports.
 */
final class ChainFilter implements FilterInterface, OpenApiParameterFilterInterface, ManagerRegistryAwareInterface, LoggerAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use LoggerAwareTrait;
    use ManagerRegistryAwareTrait;

    /**
     * @param iterable<FilterInterface> $filters
     */
    public function __construct(private readonly iterable $filters)
    {
    }

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        foreach ($this->filters as $filter) {
            if ($filter instanceof ManagerRegistryAwareInterface && !$filter->hasManagerRegistry() && $this->hasManagerRegistry()) {
                $filter->setManagerRegistry($this->getManagerRegistry());
            }

            if ($filter instanceof LoggerAwareInterface && !$filter->hasLogger() && $this->hasLogger()) {
                $filter->setLogger($this->getLogger());
            }

            $filter->apply($queryBuilder, $queryNameGenerator, $resourceClass, $operation, $context);
        }
    }

    public function getOpenApiParameters(Parameter $parameter): array
    {
        $parameters = [];
        foreach ($this->filters as $filter) {
            if ($filter instanceof OpenApiParameterFilterInterface) {
                $parameters = [...$parameters, ...(array) $filter->getOpenApiParameters($parameter)];
            }
        }

        return $parameters;
    }
}
