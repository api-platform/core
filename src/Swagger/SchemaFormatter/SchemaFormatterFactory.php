<?php

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

class SchemaFormatterFactory
{
    private $formatters;

    public function __construct(array $formatters)
    {
        $this->formatters = $formatters;
    }

    public function getFormatter($mimeType)
    {
        if (!empty($this->formatters[$mimeType])) {
            return $this->formatters[$mimeType];
        }

        throw new \Exception('Formatter for mimetype "' . $mimeType . '" not supported');
    }
}