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

trait IriSearchFilterTrait
{
    public function getDescription(string $resourceClass): array
    {
        $description = [];

        $properties = $this->getProperties();
        if (null === $properties) {
            $metadata = $this->getClassMetadata($resourceClass);
            $fieldNames = array_fill_keys($metadata->getFieldNames(), null);
            $associationNames = array_fill_keys($metadata->getAssociationNames(), null);

            $properties = array_merge($fieldNames, $associationNames);
        }

        foreach ($properties as $property => $strategy) {
            if (!$this->isPropertyMapped($property, $resourceClass, true)) {
                continue;
            }

            if ($this->isPropertyNested($property, $resourceClass)) {
                $propertyParts = $this->splitPropertyParts($property, $resourceClass);
                $field = $propertyParts['field'];
                $metadata = $this->getNestedMetadata($resourceClass, $propertyParts['associations']);
            } else {
                $field = $property;
                $metadata = $this->getClassMetadata($resourceClass);
            }

            $propertyName = $this->normalizePropertyName($property);
            if ($metadata->hasField($field)) {
                $typeOfField = $this->getType($metadata->getTypeOfField($field));
                $filterParameterNames = [$propertyName];

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $propertyName,
                        'type' => $typeOfField,
                        'required' => false,
                        'strategy' => $strategy,
                        'is_collection' => str_ends_with((string) $filterParameterName, '[]'),
                    ];
                }
            } elseif ($metadata->hasAssociation($field)) {
                $filterParameterNames = [
                    $propertyName,
                    $propertyName.'[]',
                ];

                foreach ($filterParameterNames as $filterParameterName) {
                    $description[$filterParameterName] = [
                        'property' => $propertyName,
                        'type' => 'string',
                        'required' => false,
                        'strategy' => 'exact',
                        'is_collection' => str_ends_with((string) $filterParameterName, '[]'),
                    ];
                }
            }
        }

        return $description;
    }

    public function getOpenApiParameters(Parameter $parameter): OpenApiParameter|array|null
    {
        return new OpenApiParameter(name: $parameter->getKey().'[]', in: 'query', style: 'deepObject', explode: true);
    }
}
