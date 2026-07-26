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
        $castToArray = $parameter->getCastToArray();

        if (false === $castToArray) {
            return new OpenApiParameter(name: $parameter->getKey(), in: 'query', schema: $schema ?? []);
        }

        $arraySchema = 'array' === ($schema['type'] ?? null)
            ? $schema
            : ['type' => 'array', 'items' => $schema ?? ['type' => 'string']];
        $arrayParameter = new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true, schema: $arraySchema);

        if (true === $castToArray) {
            return $arrayParameter;
        }

        // When castToArray is null (default), both singular and array forms are accepted.
        return [
            new OpenApiParameter(name: $parameter->getKey(), in: 'query', schema: $schema ?? []),
            $arrayParameter,
        ];
    }
}
