<?php

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

class JsonApiSchemaFormatter implements SchemaFormatterInterface
{
    public function supports(string $mimeType)
    {
        return 'application/vdn.api+json' === $mimeType;
    }

    public function getProperties()
    {
        return [
            'data' => [
                'type'       => 'object',
                'properties' => [
                    'attributes' => [
                        'type'       => 'object',
                        'properties' => [
                            'data' => []
                        ],
                    ],
                ],
            ],
        ];
    }

    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property)
    {
        $definitionSchema['properties']['data']['properties']['attributes']['properties']['data'][$normalizedPropertyName] = $property;
    }
}