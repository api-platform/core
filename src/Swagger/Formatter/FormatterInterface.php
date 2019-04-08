<?php

namespace ApiPlatform\Core\Swagger\Formatter;

interface FormatterInterface
{
    public function getProperties();
    public function setProperty(\ArrayObject $definitionSchema, $normalizedPropertyName, \ArrayObject $property);
}