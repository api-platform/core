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

namespace ApiPlatform\Laravel\Eloquent\Filter\JsonApi;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterProviderInterface;

final readonly class SortFilterParameterProvider implements ParameterProviderInterface
{
    public function provide(Parameter $parameter, array $parameters = [], array $context = []): ?Operation
    {
        if (!($operation = $context['operation'] ?? null)) {
            return null;
        }

        $parameters = $operation->getParameters();
        $properties = $parameter->getProperties() ?? [];
        $value = $parameter->getValue();

        // most eloquent filters work with only a single value
        if (\is_array($value) && array_is_list($value) && 1 === \count($value)) {
            $value = current($value);
        }

        if (!\is_string($value)) {
            return $operation;
        }

        $values = explode(',', $value);
        $orderBy = [];
        foreach ($values as $v) {
            $dir = SortFilter::ASC;
            if (str_starts_with($v, '-')) {
                $dir = SortFilter::DESC;
                $v = substr($v, 1);
            }

            if (\in_array($v, $properties, true)) {
                $orderBy[$v] = $dir;
            }
        }

        $parameters->add($parameter->getKey(), $parameter->setValue($orderBy));

        return $operation->withParameters($parameters);
    }
}
