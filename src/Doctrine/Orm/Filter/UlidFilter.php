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

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;

final class UlidFilter extends AbstractUuidFilter
{
    private const ULID_SCHEMA = [
        'type' => 'string',
        'format' => 'ulid',
    ];

    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();

        return [
            new OpenApiParameter(
                name: $key,
                in: $in,
                schema: self::ULID_SCHEMA,
                style: 'form',
                explode: false
            ),
            new OpenApiParameter(
                name: $key.'[]',
                in: $in,
                description: 'One or more Ulids',
                schema: [
                    'type' => 'array',
                    'items' => self::ULID_SCHEMA,
                ],
                style: 'deepObject',
                explode: true
            ),
        ];
    }

    public function getSchema(Parameter $parameter): array
    {
        return self::ULID_SCHEMA;
    }
}
