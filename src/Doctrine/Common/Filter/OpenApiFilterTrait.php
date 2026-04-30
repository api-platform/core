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
        if (false === $parameter->getCastToArray() || (isset($schema['type']) && 'array' !== $schema['type'])) {
            return new OpenApiParameter(name: $parameter->getKey(), in: 'query');
        }

        if ('array' === ($schema['type'] ?? null)) {
            $arraySchema = $schema;
        } else {
            $arraySchema = ['type' => 'array', 'items' => $schema ?? ['type' => 'string']];
        }

        $arrayParameter = new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true, schema: $arraySchema);

        // When castToArray is null (default), both singular and array forms are accepted
        if (null === $parameter->getCastToArray()) {
            return [
                new OpenApiParameter(name: $parameter->getKey(), in: 'query'),
                $arrayParameter,
            ];
        }

        return $arrayParameter;
    }
}
