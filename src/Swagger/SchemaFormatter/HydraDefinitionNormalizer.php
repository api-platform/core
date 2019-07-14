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

final class HydraDefinitionNormalizer implements DefinititionNormalizerInterface
{
    public function supports(string $mimeType): bool
    {
        return 'application/ld+json' === $mimeType;
    }

    public function buildBaseSchemaFormat(): array
    {
        return [
            'hydra:member' => [
                'type' => 'object',
                'properties' => [
                    '@type' => [
                        'type' => 'string',
                    ],
                    '@id' => [
                        'type' => 'integer',
                    ],
                ],
            ],
        ];
    }

    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property, PropertyMetadata $propertyMetadata): void
    {
        if (null !== $propertyMetadata->getType()
            && 'object' === $propertyMetadata->getType()->getBuiltinType()
            && isset($property['$ref'])
        ) {
            // @todo: Fix one to many statement.
//            if (false) {
//                $data = [
//                    'type' => 'object',
//                    'properties' => [
//                        $data,
//                    ],
//                ];
//            }

            $definitionSchema['properties']['hydra:member']['properties'][$normalizedPropertyName] = $property;

            return;
        }

        $definitionSchema['properties']['hydra:member']['properties'][$normalizedPropertyName] = $property;
    }
}
