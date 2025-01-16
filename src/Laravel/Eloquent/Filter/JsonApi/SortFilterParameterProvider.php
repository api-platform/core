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
        $properties = $parameter->getExtraProperties()['_properties'] ?? [];
        $value = $parameter->getValue();
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

            if (\array_key_exists($v, $properties)) {
                $orderBy[$properties[$v]] = $dir;
            }
        }

        $parameters->add($parameter->getKey(), $parameter->withExtraProperties(
            ['_api_values' => $orderBy] + $parameter->getExtraProperties()
        ));

        return $operation->withParameters($parameters);
    }
}
