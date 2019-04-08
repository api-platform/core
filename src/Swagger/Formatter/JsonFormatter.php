<?php

namespace ApiPlatform\Core\Swagger\Formatter;

class JsonFormatter implements FormatterInterface
{

    public function getProperties()
    {
        return [];
    }

    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property){
        $definitionSchema['properties'][$normalizedPropertyName] = $property;
    }
}