<?php

namespace ApiPlatform\Core\Tests\Swagger\SchemaFormatter;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonSchemaFormatter;
use PHPUnit\Framework\TestCase;

class JsonSchemaFormatterTest extends TestCase
{
    public function testSupports()
    {
        $schemaFormatter = new JsonSchemaFormatter();
        $this->assertTrue($schemaFormatter->supports('application/json'));
    }

    public function testSupportsNotSupported()
    {
        $schemaFormatter = new JsonSchemaFormatter();
        $this->assertFalse($schemaFormatter->supports('application/jso'));
    }

    public function testBuildBaseSchemaFormat()
    {
        $schemaFormatter = new JsonSchemaFormatter();
        $this->assertEquals([], $schemaFormatter->buildBaseSchemaFormat());
    }

    public function testSetProperty()
    {
        $schemaFormatter = new JsonSchemaFormatter();
        $definitionSchema = new \ArrayObject([]);
        $normalizedPropertyName = 'test';
        $property = new \ArrayObject([
            'test' => 'foo',
        ]);
        $propertyMetadata = new PropertyMetadata();

        $definitionSchema['properties'] = $schemaFormatter->buildBaseSchemaFormat();
        $schemaFormatter->setProperty($definitionSchema, $normalizedPropertyName, $property, $propertyMetadata);

        $this->assertEquals(
            new \ArrayObject([
                'properties' => [
                    'test' => new \ArrayObject([
                        'test' => 'foo',
                    ]),
                ],
            ]),
            $definitionSchema
        );
    }
}