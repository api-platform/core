<?php

namespace ApiPlatform\Core\Swagger\Formatter;

class JsonApiFormatter implements FormatterInterface
{
    public function getProperties()
    {
        return [
            'data' => [
                'type'       => 'object',
                'properties' => [
                    'attributes' => [
                        'type'       => 'object',
                        'properties' => [],
                    ],
                ],
            ],
        ];
    }

    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property)
    {
        $definitionSchema['properties']['data']['properties']['attributes']['properties'][$normalizedPropertyName] = $property;
    }
}