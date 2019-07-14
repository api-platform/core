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
use ApiPlatform\Core\Swagger\SchemaFormatter\DefaultDefinitionNormalizer;
use PHPUnit\Framework\TestCase;

class DefaultSchemaFormatterTest extends TestCase
{
    public function testSupports()
    {
        $schemaFormatter = new DefaultDefinitionNormalizer();
        $this->assertTrue($schemaFormatter->supports('application/json'));
    }

    public function testSupportsNotSupported()
    {
        $schemaFormatter = new DefaultDefinitionNormalizer();
        $this->assertTrue($schemaFormatter->supports('application/jso'));
    }

    public function testBuildBaseSchemaFormat()
    {
        $schemaFormatter = new DefaultDefinitionNormalizer();
        $this->assertEquals([], $schemaFormatter->buildBaseSchemaFormat());
    }

    public function testSetProperty()
    {
        $schemaFormatter = new DefaultDefinitionNormalizer();
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
