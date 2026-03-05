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

namespace ApiPlatform\Mcp\Tests\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Mcp\JsonSchema\SchemaFactory;
use PHPUnit\Framework\TestCase;

class SchemaFactoryTest extends TestCase
{
    public function testFlatSchemaPassesThrough(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Dummy'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/Dummy';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        $this->assertSame(['name' => ['type' => 'string']], $arr['properties']);
        $this->assertArrayNotHasKey('$ref', $arr);
        $this->assertArrayNotHasKey('definitions', $arr);
        $this->assertArrayNotHasKey('$schema', $arr);
    }

    public function testRefIsResolved(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Wrapper'] = new \ArrayObject([
            '$ref' => '#/definitions/Actual',
        ]);
        $definitions['Actual'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/Wrapper';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        $this->assertSame(['id' => ['type' => 'integer']], $arr['properties']);
        $this->assertArrayNotHasKey('$ref', $arr);
        $this->assertArrayNotHasKey('definitions', $arr);
    }

    public function testAllOfIsMerged(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Root'] = new \ArrayObject([
            'description' => 'A dummy resource',
            'allOf' => [
                ['$ref' => '#/definitions/Part1'],
                ['$ref' => '#/definitions/Part2'],
            ],
        ]);
        $definitions['Part1'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ]);
        $definitions['Part2'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/Root';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'jsonld');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        $this->assertSame('A dummy resource', $arr['description']);
        $this->assertArrayHasKey('name', $arr['properties']);
        $this->assertArrayHasKey('email', $arr['properties']);
        $this->assertSame(['name'], $arr['required']);
        $this->assertArrayNotHasKey('allOf', $arr);
        $this->assertArrayNotHasKey('$ref', $arr);
        $this->assertArrayNotHasKey('definitions', $arr);
    }

    public function testMissingTypeGetsObjectAdded(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['NoType'] = new \ArrayObject([
            'properties' => [
                'foo' => ['type' => 'string'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/NoType';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        $this->assertSame(['foo' => ['type' => 'string']], $arr['properties']);
    }

    public function testNestedRefInsideAllOf(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Root'] = new \ArrayObject([
            'allOf' => [
                ['$ref' => '#/definitions/Middle'],
            ],
        ]);
        $definitions['Middle'] = new \ArrayObject([
            '$ref' => '#/definitions/Leaf',
        ]);
        $definitions['Leaf'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'deep' => ['type' => 'boolean'],
            ],
            'required' => ['deep'],
        ]);
        $innerSchema['$ref'] = '#/definitions/Root';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        $this->assertSame(['deep' => ['type' => 'boolean']], $arr['properties']);
        $this->assertSame(['deep'], $arr['required']);
    }

    public function testCircularRefFallsBackToObject(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['A'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'b' => ['$ref' => '#/definitions/B'],
            ],
        ]);
        $definitions['B'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'a' => ['$ref' => '#/definitions/A'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/A';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        // A.b resolves B, B.a resolves A again, then A.b hits the cycle and breaks
        $this->assertSame(['type' => 'object'], $arr['properties']['b']['properties']['a']['properties']['b']);
    }

    public function testAllOfInsidePropertyIsResolved(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Root'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'nested' => [
                    'allOf' => [
                        ['$ref' => '#/definitions/PartA'],
                        ['$ref' => '#/definitions/PartB'],
                    ],
                ],
            ],
        ]);
        $definitions['PartA'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'x' => ['type' => 'integer'],
            ],
        ]);
        $definitions['PartB'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'y' => ['type' => 'string'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/Root';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame('object', $arr['type']);
        $this->assertArrayHasKey('x', $arr['properties']['nested']['properties']);
        $this->assertArrayHasKey('y', $arr['properties']['nested']['properties']);
        $this->assertArrayNotHasKey('allOf', $arr['properties']['nested']);
    }

    public function testSameRefUsedTwiceIsResolvedBothTimes(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Root'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'address' => ['$ref' => '#/definitions/Address'],
                'billingAddress' => ['$ref' => '#/definitions/Address'],
            ],
        ]);
        $definitions['Address'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'street' => ['type' => 'string'],
            ],
        ]);
        $innerSchema['$ref'] = '#/definitions/Root';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        // Both properties should be fully resolved (not circular-ref fallback)
        $this->assertSame(['street' => ['type' => 'string']], $arr['properties']['address']['properties']);
        $this->assertSame(['street' => ['type' => 'string']], $arr['properties']['billingAddress']['properties']);
    }

    public function testUnresolvableRefFallsBackToObject(): void
    {
        $innerSchema = new Schema(Schema::VERSION_JSON_SCHEMA);
        unset($innerSchema['$schema']);
        $definitions = $innerSchema->getDefinitions();
        $definitions['Root'] = new \ArrayObject([
            '$ref' => '#/definitions/DoesNotExist',
        ]);
        $innerSchema['$ref'] = '#/definitions/Root';

        $inner = $this->createMock(SchemaFactoryInterface::class);
        $inner->method('buildSchema')->willReturn($innerSchema);

        $factory = new SchemaFactory($inner);
        $result = $factory->buildSchema('App\\Dummy', 'json');

        $arr = $result->getArrayCopy();
        $this->assertSame(['type' => 'object'], $arr);
    }
}
