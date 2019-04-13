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
    public function supports(string $mimeType)
    {
        return 'application/vnd.api+json' === $mimeType;
    }

    public function getProperties()
    {
        return [
            'data' => [
                'type'       => 'object',
                'properties' => [
                    'type'       => [
                        'type' => 'string',
                    ],
                    'id'         => [
                        'type' => 'integer',
                    ],
                    'attributes' => [
                        'type'       => 'object',
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
    ) {
        if ($normalizedPropertyName === 'id') {
            $definitionSchema['properties']['data']['properties'][$normalizedPropertyName] = $property;
            $normalizedPropertyName = '_id';
        }

        if ($propertyMetadata->getType()->getBuiltinType() === 'object') {
            $data = [
                'type'       => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                    ],
                    'id'   => [
                        'type' => 'string',
                    ],
                ],
            ];
            dump($property);
            if (true) {
                $data = [
                    'type'       => 'object',
                    'properties' => [
                        $data,
                    ],
                ];
            }

            $definitionSchema['properties']['data']['properties']['relationships'] = [
                'type'       => 'object',
                'properties' => [
                    $normalizedPropertyName => [
                        'type'       => 'object',
                        'properties' => [
                            'links' => [
                                'type'       => 'object',
                                'properties' => [
                                    'self'    => [
                                        'type' => 'string',
                                    ],
                                    'related' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'data'  => $data,
                        ],
                    ],
                ],
            ];

        } else {
            $definitionSchema['properties']['data']['properties']['attributes']['properties'][$normalizedPropertyName] = $property;
        }
    }
}
