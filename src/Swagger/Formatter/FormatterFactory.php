<?php

namespace ApiPlatform\Core\Swagger\Formatter;

class FormatterFactory
{
    private $formatters;

    public function __construct()
    {
        //@todo: Improve this code to get formatters from the users config so he can add his own formatters.
        $this->formatters = [
            'application/json'         => new JsonFormatter(),
            'application/vnd.api+json' => new JsonApiFormatter(),
            'application/ld+json'      => new JsonFormatter(),//@todo: write own formatter
        ];
    }

    public function getFormatter($mimeType)
    {
        if (!empty($this->formatters[$mimeType])) {
            return $this->formatters[$mimeType];
        }

        throw new \Exception('Formatter for mimetype "' . $mimeType . '" not supported');
    }
}