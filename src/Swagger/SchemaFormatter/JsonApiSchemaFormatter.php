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

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;

class JsonApiSchemaFormatter implements SchemaFormatterInterface
{
    public function supports(string $mimeType): bool
    {
        return 'application/vnd.api+json' === $mimeType;
    }

    public function buildBaseSchemaFormat(): array
    {
        return [
            'data' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                    ],
                    'id' => [
                        'type' => 'integer',
                    ],
                    'attributes' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
        ];
    }

    public function setProperty(
        \ArrayObject $definitionSchema,
        $normalizedPropertyName,
        \ArrayObject $property,
        PropertyMetadata $propertyMetadata
    ): void {
        if ('id' === $normalizedPropertyName) {
            $definitionSchema['properties']['data']['properties'][$normalizedPropertyName] = $property;
            $normalizedPropertyName = '_id';
        }

        if (null !== $propertyMetadata->getType()
            && 'object' === $propertyMetadata->getType()->getBuiltinType()
            && isset($property['$ref'])
        ) {
            $data = [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                    ],
                    'id' => [
                        'type' => 'string',
                    ],
                ],
            ];
// @todo: Fix one to many statement.
//            if (false) {
//                $data = [
//                    'type' => 'object',
//                    'properties' => [
//                        $data,
//                    ],
//                ];
//            }

            $definitionSchema['properties']['data']['properties']['relationships'] = [
                'type' => 'object',
                'properties' => [
                    $normalizedPropertyName => [
                        'type' => 'object',
                        'properties' => [
                            'links' => [
                                'type' => 'object',
                                'properties' => [
                                    'self' => [
                                        'type' => 'string',
                                    ],
                                    'related' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'data' => $data,
                        ],
                    ],
                ],
            ];

        } else {
            $definitionSchema['properties']['data']['properties']['attributes']['properties'][$normalizedPropertyName] = $property;
        }
    }
}
