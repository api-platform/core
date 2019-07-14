<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Tests\Swagger\SchemaFormatter;

use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonApiDefinitionNormalizer;
use PHPUnit\Framework\TestCase;

class JsonApiSchemaFormatterTest extends TestCase
{
    public function testSupports()
    {
        $schemaFormatter = new JsonApiDefinitionNormalizer();
        $this->assertTrue($schemaFormatter->supports('application/vnd.api+json'));
    }

    public function testSupportsNotSupported()
    {
        $schemaFormatter = new JsonApiDefinitionNormalizer();
        $this->assertFalse($schemaFormatter->supports('application/jso'));
    }

    public function testBuildBaseSchemaFormat()
    {
        $schemaFormatter = new JsonApiDefinitionNormalizer();
        $this->assertEquals(
            [
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                        ],
                        'id' => [
                            'type' => 'integer',
                        ],
                        'attributes' => [
                            'type' => 'object',
                            'properties' => [],
                        ],
                    ],
                ],
            ],
            $schemaFormatter->buildBaseSchemaFormat()
        );
    }

    public function testSetProperty()
    {
        $schemaFormatter = new JsonApiDefinitionNormalizer();
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
                    'data' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                            ],
                            'id' => [
                                'type' => 'integer',
                            ],
                            'attributes' => [
                                'type' => 'object',
                                'properties' => [
                                    'test' => new \ArrayObject([
                                        'test' => 'foo',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            $definitionSchema
        );
    }
}
