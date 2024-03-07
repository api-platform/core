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

namespace ApiPlatform\Serializer\Parameter;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Serializer\Filter\FilterInterface;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\Util\RequestAttributesExtractor;
use Psr\Container\ContainerInterface;

/**
 * @experimental
 */
final class SerializerFilterParameterProvider implements ParameterProviderInterface
{
    public function __construct(private readonly ?ContainerInterface $filterLocator)
    {
    }

    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?HttpOperation
    {
        if (null === ($request = $context['request'] ?? null) || null === ($operation = $context['operation'] ?? null)) {
            return null;
        }

        $filter = $parameter->getFilter();
        if (\is_string($filter)) {
            $filter = $this->getFilter($filter);
        }

        if ($filter instanceof FilterInterface) {
            $context = $operation->getNormalizationContext();
            $filter->apply($request, true, RequestAttributesExtractor::extractAttributes($request), $context);

            return $operation->withNormalizationContext($context);
        }

        return null;
    }

    /**
     * Gets a filter with a backward compatibility.
     */
    private function getFilter(string $filterId): ?FilterInterface
    {
        if ($this->filterLocator && $this->filterLocator->has($filterId)) {
            return $this->filterLocator->get($filterId);
        }

        return null;
    }
}
