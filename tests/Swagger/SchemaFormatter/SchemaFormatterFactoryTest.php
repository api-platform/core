<?php

namespace ApiPlatform\Core\Tests\Swagger\SchemaFormatter;

use ApiPlatform\Core\Exception\FormatterNotFoundException;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonApiSchemaFormatter;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonSchemaFormatter;
use ApiPlatform\Core\Swagger\SchemaFormatter\SchemaFormatterFactory;
use PHPUnit\Framework\TestCase;

class SchemaFormatterFactoryTest extends TestCase
{
    public function testGetFormatter()
    {
        $schemaFormatterFactory = new SchemaFormatterFactory([
            new JsonApiSchemaFormatter(),
            new JsonSchemaFormatter(),
        ]);
        $formatter = $schemaFormatterFactory->getFormatter('application/json');
        $this->assertInstanceOf(JsonSchemaFormatter::class, $formatter);
    }

    public function testGetFormatterException()
    {
        $schemaFormatterFactory = new SchemaFormatterFactory([
            new JsonApiSchemaFormatter(),
            new JsonSchemaFormatter(),
        ]);

        $this->expectException(FormatterNotFoundException::class);
        $schemaFormatterFactory->getFormatter('application/json-test');
    }

    public function testGetFormatterExceptionNoFormatters()
    {
        $schemaFormatterFactory = new SchemaFormatterFactory([]);

        $this->expectException(FormatterNotFoundException::class);
        $schemaFormatterFactory->getFormatter('application/json-test');
    }
}