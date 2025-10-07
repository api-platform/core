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

namespace ApiPlatform\Tests\Functional\JsonSchema;

use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7426\Boat;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6212\Nest;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedToDummyFriend;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class JsonLdJsonSchemaTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected SchemaFactoryInterface $schemaFactory;
    protected OperationMetadataFactoryInterface $operationMetadataFactory;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [RelatedDummy::class, ThirdLevel::class, RelatedToDummyFriend::class, Boat::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $this->operationMetadataFactory = self::getContainer()->get('api_platform.metadata.operation.metadata_factory');
    }

    public function testSubSchemaJsonLd(): void
    {
        $schema = $this->schemaFactory->buildSchema(BagOfTests::class, 'jsonld');

        $expectedBagOfTestsSchema = new \ArrayObject([
            'allOf' => [
                [
                    '$ref' => '#/definitions/HydraItemBaseSchema',
                ],
                new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'id' => new \ArrayObject([
                            'type' => 'integer',
                            'readOnly' => true,
                        ]),
                        'description' => new \ArrayObject([
                            'maxLength' => 255,
                        ]),
                        'tests' => new \ArrayObject([
                            'type' => 'string',
                            'foo' => 'bar',
                        ]),
                        'nonResourceTests' => new \ArrayObject([
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/definitions/NonResourceTestEntity.jsonld-read',
                            ],
                        ]),
                        'type' => new \ArrayObject([
                            '$ref' => '#/definitions/TestEntity.jsonld-read',
                        ]),
                    ],
                ]),
            ],
        ]);

        $expectedNonResourceTestEntitySchema = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'id' => new \ArrayObject([
                    'type' => 'integer',
                    'readOnly' => true,
                ]),
                'nullableString' => new \ArrayObject([
                    'type' => [
                        'string',
                        'null',
                    ],
                ]),
                'nullableInt' => new \ArrayObject([
                    'type' => [
                        'integer',
                        'null',
                    ],
                ]),
            ],
        ]);

        $expectedTestEntitySchema = new \ArrayObject([
            'allOf' => [
                [
                    '$ref' => '#/definitions/HydraItemBaseSchema',
                ],
                new \ArrayObject([
                    'type' => 'object',
                    'properties' => [
                        'id' => new \ArrayObject([
                            'type' => 'integer',
                            'readOnly' => true,
                        ]),
                        'nullableString' => new \ArrayObject([
                            'type' => [
                                'string',
                                'null',
                            ],
                        ]),
                        'nullableInt' => new \ArrayObject([
                            'type' => [
                                'integer',
                                'null',
                            ],
                        ]),
                    ],
                ]),
            ],
        ]);

        $this->assertArrayHasKey('definitions', $schema);
        $this->assertArrayHasKey('BagOfTests.jsonld-read', $schema['definitions']);
        $this->assertArrayHasKey('NonResourceTestEntity.jsonld-read', $schema['definitions']);
        $this->assertArrayHasKey('TestEntity.jsonld-read', $schema['definitions']);

        $this->assertEquals($expectedBagOfTestsSchema, $schema['definitions']['BagOfTests.jsonld-read']);
        $this->assertEquals($expectedNonResourceTestEntitySchema, $schema['definitions']['NonResourceTestEntity.jsonld-read']);
        $this->assertEquals($expectedTestEntitySchema, $schema['definitions']['TestEntity.jsonld-read']);

        $this->assertEquals('#/definitions/BagOfTests.jsonld-read', $schema['$ref']);
    }

    public function testSchemaJsonLdCollection(): void
    {
        $schema = $this->schemaFactory->buildSchema(BagOfTests::class, 'jsonld', forceCollection: true);

        $this->assertArrayHasKey('definitions', $schema);
        $this->assertArrayHasKey('BagOfTests.jsonld-read', $schema['definitions']);
        $this->assertArrayHasKey('NonResourceTestEntity.jsonld-read', $schema['definitions']);
        $this->assertArrayHasKey('TestEntity.jsonld-read', $schema['definitions']);
        $this->assertArrayHasKey('HydraItemBaseSchema', $schema['definitions']);
        $this->assertArrayHasKey('HydraCollectionBaseSchema', $schema['definitions']);

        $this->assertEquals(['$ref' => '#/definitions/HydraCollectionBaseSchema'], $schema['allOf'][0]);
        $this->assertEquals(['$ref' => '#/definitions/BagOfTests.jsonld-read'], $schema['allOf'][1]['properties']['hydra:member']['items']);
    }

    public function testArraySchemaWithMultipleUnionTypes(): void
    {
        $schema = $this->schemaFactory->buildSchema(Nest::class, 'jsonld', 'output');

        $this->assertContains(['$ref' => '#/definitions/Robin.jsonld'], $schema['definitions']['Nest.jsonld']['allOf'][1]['properties']['owner']['anyOf']);
        $this->assertContains(['$ref' => '#/definitions/Wren.jsonld'], $schema['definitions']['Nest.jsonld']['allOf'][1]['properties']['owner']['anyOf']);
        $this->assertContains(['type' => 'null'], $schema['definitions']['Nest.jsonld']['allOf'][1]['properties']['owner']['anyOf']);

        $this->assertArrayHasKey('Nest.jsonld', $schema['definitions']);
    }

    public function testSchemaWithoutGetOperation(): void
    {
        $schema = $this->schemaFactory->buildSchema(Boat::class, 'jsonld', 'output', $this->operationMetadataFactory->create('_api_/boats{._format}_get_collection'));

        $this->assertEquals(['$ref' => '#/definitions/HydraItemBaseSchema'], $schema->getDefinitions()['Boat.jsonld-boat.read']['allOf'][0]);
    }
}
