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

namespace ApiPlatform\Doctrine\Common\Filter;

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
trait OpenApiFilterTrait
{
    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        $schema = $parameter->getSchema();
        $isArraySchema = 'array' === ($schema['type'] ?? null);
        $castToArray = $parameter->getCastToArray();

        // Use non-array notation if:
        // 1. Schema type is explicitly set to a non-array type (string, number, etc.)
        // 2. OR castToArray is explicitly false
        $hasNonArraySchema = null !== $schema && !$isArraySchema;

        if ($hasNonArraySchema || false === $castToArray) {
            return new OpenApiParameter(name: $parameter->getKey(), in: 'query');
        }

        return new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true);
    }
}
