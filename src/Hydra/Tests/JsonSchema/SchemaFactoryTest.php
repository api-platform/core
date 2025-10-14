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

namespace ApiPlatform\Hydra\Tests\JsonSchema;

use ApiPlatform\Hydra\JsonSchema\SchemaFactory;
use ApiPlatform\Hydra\Tests\Fixtures\Dummy;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SchemaFactoryTest extends TestCase
{
    use ProphecyTrait;

    private SchemaFactory $schemaFactory;

    protected function setUp(): void
    {
        $resourceMetadataFactoryCollection = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryCollection->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                new ApiResource(operations: [
                    'get' => new Get(name: 'get'),
                ]),
            ])
        );

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, ['enable_getter_setter_extraction' => true, 'schema_type' => Schema::TYPE_OUTPUT])->willReturn(new PropertyNameCollection(['id', 'name']));
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', Argument::type('array'))->willReturn(new ApiProperty(identifier: true));
        $propertyMetadataFactory->create(Dummy::class, 'name', Argument::type('array'))->willReturn(new ApiProperty());

        $definitionNameFactory = new DefinitionNameFactory();

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactoryCollection->reveal(),
            propertyNameCollectionFactory: $propertyNameCollectionFactory->reveal(),
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );

        $this->schemaFactory = new SchemaFactory(
            $baseSchemaFactory,
            [],
            $definitionNameFactory,
            $resourceMetadataFactoryCollection->reveal(),
        );
    }

    public function testBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);

        $this->assertTrue($resultSchema->isDefined());
        $this->assertSame('Dummy.jsonld', $resultSchema->getRootDefinitionKey());
    }

    public function testCustomFormatBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'json');

        $this->assertTrue($resultSchema->isDefined());
        $this->assertSame('Dummy', $resultSchema->getRootDefinitionKey());
    }

    public function testHasRootDefinitionKeyBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        $this->assertTrue(isset($definitions[$rootDefinitionKey]['allOf'][1]['properties']));
        $this->assertEquals($definitions[$rootDefinitionKey]['allOf'][0], ['$ref' => '#/definitions/HydraItemBaseSchema']);

        $properties = $definitions['HydraItemBaseSchema']['properties'];
        $this->assertArrayHasKey('@context', $properties);
        $this->assertEquals(
            [
                'oneOf' => [
                    ['type' => 'string'],
                    [
                        'type' => 'object',
                        'properties' => [
                            '@vocab' => [
                                'type' => 'string',
                            ],
                            'hydra' => [
                                'type' => 'string',
                                'enum' => [ContextBuilder::HYDRA_NS],
                            ],
                        ],
                        'required' => ['@vocab', 'hydra'],
                        'additionalProperties' => true,
                    ],
                ],
            ],
            $properties['@context']
        );
        $this->assertArrayHasKey('@type', $properties);
        $this->assertArrayHasKey('@id', $properties);
    }

    public function testSchemaTypeBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, new GetCollection());
        $this->assertNull($resultSchema->getRootDefinitionKey());
        $hydraCollectionSchema = $resultSchema['definitions']['HydraCollectionBaseSchema'];
        $properties = $hydraCollectionSchema['properties'];
        $this->assertArrayHasKey('hydra:totalItems', $properties);
        $this->assertArrayHasKey('hydra:view', $properties);
        $this->assertArrayHasKey('hydra:search', $properties);
        $this->assertArrayNotHasKey('@context', $properties);

        $this->assertTrue(isset($properties['hydra:view']));
        $this->assertArrayHasKey('properties', $properties['hydra:view']);
        $this->assertArrayHasKey('hydra:first', $properties['hydra:view']['properties']);
        $this->assertArrayHasKey('hydra:last', $properties['hydra:view']['properties']);
        $this->assertArrayHasKey('hydra:previous', $properties['hydra:view']['properties']);
        $this->assertArrayHasKey('hydra:next', $properties['hydra:view']['properties']);

        $forcedCollection = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, null, null, null, true);
        $this->assertEquals($resultSchema['allOf'][0]['$ref'], $forcedCollection['allOf'][0]['$ref']);
    }

    public function testSchemaTypeBuildSchemaWithoutPrefix(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, new GetCollection(), null, [ContextBuilder::HYDRA_CONTEXT_HAS_PREFIX => false]);
        $this->assertNull($resultSchema->getRootDefinitionKey());
        $hydraCollectionSchema = $resultSchema['definitions']['HydraCollectionBaseSchema'];
        $properties = $hydraCollectionSchema['properties'];
        $this->assertArrayHasKey('totalItems', $properties);
        $this->assertArrayHasKey('view', $properties);
        $this->assertArrayHasKey('search', $properties);
    }
}
