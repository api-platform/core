<?php

namespace ApiPlatform\Core\Swagger\SchemaFormatter;

class SchemaFormatterFactory
{
    /** @var SchemaFormatterInterface[] */
    private $formatters;

    public function __construct(/* iterable */  $formatters)
    {
        $this->formatters = $formatters;
    }

    public function getFormatter($mimeType)
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->supports($mimeType)) {
                return $formatter;
            }
        }

        throw new \Exception('Formatter for mimetype "' . $mimeType . '" not supported');
    }
}