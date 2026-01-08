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

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $schema = $parameter->getSchema();
        $isArraySchema = 'array' === ($schema['type'] ?? null);
        $hasNonArrayType = isset($schema['type']) && 'array' !== $schema['type'];

        $baseSchema = self::ULID_SCHEMA;
        $arraySchema = ['type' => 'array', 'items' => $baseSchema];

        if ($isArraySchema) {
            return new OpenApiParameter(
                name: $parameter->getKey().'[]',
                in: $in,
                schema: $arraySchema,
                style: 'deepObject',
                explode: true,
            );
        }

        if ($hasNonArrayType) {
            return new OpenApiParameter(
                name: $parameter->getKey(),
                in: $in,
                schema: $baseSchema,
            );
        }

        // oneOf or no specific type constraint - return both with explicit schemas
        return [
            new OpenApiParameter(
                name: $parameter->getKey(),
                in: $in,
                schema: $baseSchema,
            ),
            new OpenApiParameter(
                name: $parameter->getKey().'[]',
                in: $in,
                schema: $arraySchema,
                style: 'deepObject',
                explode: true,
            ),
        ];
    }

    public function getSchema(Parameter $parameter): array
    {
        if (false === $parameter->getCastToArray()) {
            return self::ULID_SCHEMA;
        }

        return [
            'oneOf' => [
                self::ULID_SCHEMA,
                [
                    'type' => 'array',
                    'items' => self::ULID_SCHEMA,
                ],
            ],
        ];
    }
}
