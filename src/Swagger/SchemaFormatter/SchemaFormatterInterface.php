<?php

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

interface SchemaFormatterInterface
{
    public function supports(string $mimeType);
    public function getProperties();
    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property);
}