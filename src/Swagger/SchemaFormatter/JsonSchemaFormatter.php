<?php

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

class JsonSchemaFormatter implements SchemaFormatterInterface
{
    public function supports(string $mimeType)
    {
        return 'application/json' === $mimeType;
    }

    public function getProperties()
    {
        return [];
    }

    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property)
    {
        $definitionSchema['properties'][$normalizedPropertyName] = $property;
    }

}
