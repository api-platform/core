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

use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class ExactFilter implements FilterInterface, OpenApiParameterFilterInterface
{
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        if (!$parameter = $context['parameter'] ?? null) {
            return;
        }

        \assert($parameter instanceof Parameter);

        $values = (array) $parameter->getValue();

        //TODO: handle nested properties
        $property = $parameter->getProperty();

        $aggregationBuilder
            ->match()
            ->field($property)
            ->in($values);
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        return new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true);
    }

    public function getDescription(string $resourceClass): array
    {
        return [];
    }
}
