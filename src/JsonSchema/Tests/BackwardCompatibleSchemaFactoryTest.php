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

namespace ApiPlatform\JsonSchema\Tests;

use ApiPlatform\JsonSchema\BackwardCompatibleSchemaFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use PHPUnit\Framework\TestCase;

class BackwardCompatibleSchemaFactoryTest extends TestCase
{
    public function testWithSingleType(): void
    {
        $schema = new Schema();
        $schema->setDefinitions(new \ArrayObject([
            'a' => new \ArrayObject([
                'properties' => new \ArrayObject([
                    'foo' => new \ArrayObject(['type' => 'integer', 'exclusiveMinimum' => 0, 'exclusiveMaximum' => 1]),
                ]),
            ]),
        ]));
        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($schema);
        $schemaFactory = new BackwardCompatibleSchemaFactory($schemaFactory);
        $schema = $schemaFactory->buildSchema('a', serializerContext: [BackwardCompatibleSchemaFactory::SCHEMA_DRAFT4_VERSION => true]);
        $schema = $schema->getDefinitions()['a'];

        $this->assertTrue($schema['properties']['foo']['exclusiveMinimum']);
        $this->assertTrue($schema['properties']['foo']['exclusiveMaximum']);
        $this->assertEquals($schema['properties']['foo']['minimum'], 0);
        $this->assertEquals($schema['properties']['foo']['maximum'], 1);
    }

    public function testWithMultipleType(): void
    {
        $schema = new Schema();
        $schema->setDefinitions(new \ArrayObject([
            'a' => new \ArrayObject([
                'properties' => new \ArrayObject([
                    'foo' => new \ArrayObject(['type' => ['number', 'null'], 'exclusiveMinimum' => 0, 'exclusiveMaximum' => 1]),
                ]),
            ]),
        ]));
        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($schema);
        $schemaFactory = new BackwardCompatibleSchemaFactory($schemaFactory);
        $schema = $schemaFactory->buildSchema('a', serializerContext: [BackwardCompatibleSchemaFactory::SCHEMA_DRAFT4_VERSION => true]);
        $schema = $schema->getDefinitions()['a'];

        $this->assertTrue($schema['properties']['foo']['exclusiveMinimum']);
        $this->assertTrue($schema['properties']['foo']['exclusiveMaximum']);
        $this->assertEquals($schema['properties']['foo']['minimum'], 0);
        $this->assertEquals($schema['properties']['foo']['maximum'], 1);
    }

    public function testWithoutNumber(): void
    {
        $schema = new Schema();
        $schema->setDefinitions(new \ArrayObject([
            'a' => new \ArrayObject([
                'properties' => new \ArrayObject([
                    'foo' => new \ArrayObject(['type' => ['string', 'null'], 'exclusiveMinimum' => 0, 'exclusiveMaximum' => 1]),
                ]),
            ]),
        ]));
        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($schema);
        $schemaFactory = new BackwardCompatibleSchemaFactory($schemaFactory);
        $schema = $schemaFactory->buildSchema('a', serializerContext: [BackwardCompatibleSchemaFactory::SCHEMA_DRAFT4_VERSION => true]);
        $schema = $schema->getDefinitions()['a'];

        $this->assertEquals($schema['properties']['foo']['exclusiveMinimum'], 0);
        $this->assertEquals($schema['properties']['foo']['exclusiveMaximum'], 1);
    }

    public function testWithoutFlag(): void
    {
        $schema = new Schema();
        $schema->setDefinitions(new \ArrayObject([
            'a' => new \ArrayObject([
                'properties' => new \ArrayObject([
                    'foo' => new \ArrayObject(['type' => ['string', 'null'], 'exclusiveMinimum' => 0, 'exclusiveMaximum' => 1]),
                ]),
            ]),
        ]));
        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->method('buildSchema')->willReturn($schema);
        $schemaFactory = new BackwardCompatibleSchemaFactory($schemaFactory);
        $schema = $schemaFactory->buildSchema('a', serializerContext: [BackwardCompatibleSchemaFactory::SCHEMA_DRAFT4_VERSION => false]);
        $schema = $schema->getDefinitions()['a'];

        $this->assertEquals($schema['properties']['foo']['exclusiveMinimum'], 0);
        $this->assertEquals($schema['properties']['foo']['exclusiveMaximum'], 1);
    }
}
