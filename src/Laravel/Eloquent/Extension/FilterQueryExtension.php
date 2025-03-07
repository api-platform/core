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

namespace ApiPlatform\Laravel\Eloquent\Extension;

use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterNotFound;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;

final readonly class FilterQueryExtension implements QueryExtensionInterface
{
    public function __construct(
        private ContainerInterface $filterLocator,
    ) {
    }

    /**
     * @param Builder<Model>        $builder
     * @param array<string, string> $uriVariables
     * @param array<string, mixed>  $context
     *
     * @return Builder<Model>
     */
    public function apply(Builder $builder, array $uriVariables, Operation $operation, $context = []): Builder
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $builder;
        }

        $context['uri_variables'] = $uriVariables;
        $context['operation'] = $operation;

        foreach ($operation->getParameters() ?? [] as $parameter) {
            if (null === ($values = $parameter->getValue()) || $values instanceof ParameterNotFound) {
                continue;
            }

            if (null === ($filterId = $parameter->getFilter())) {
                continue;
            }

            $filter = $filterId instanceof FilterInterface ? $filterId : ($this->filterLocator->has($filterId) ? $this->filterLocator->get($filterId) : null);
            if ($filter instanceof FilterInterface) {
                $builder = $filter->apply($builder, $values, $parameter, $context + ($parameter->getFilterContext() ?? []));
            }
        }

        return $builder;
    }
}
