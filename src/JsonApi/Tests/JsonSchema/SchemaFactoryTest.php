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

namespace ApiPlatform\JsonApi\Tests\JsonSchema;

use ApiPlatform\JsonApi\JsonSchema\SchemaFactory;
use ApiPlatform\JsonApi\Tests\Fixtures\Dummy;
use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class SchemaFactoryTest extends TestCase
{
    use ProphecyTrait;

    private SchemaFactory $schemaFactory;

    protected function setUp(): void
    {
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withOperations(new Operations([
                    'get' => (new Get())->withName('get'),
                ])),
            ])
        );
        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, ['enable_getter_setter_extraction' => true, 'schema_type' => Schema::TYPE_OUTPUT])->willReturn(new PropertyNameCollection());
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $definitionNameFactory = new DefinitionNameFactory(null);

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            propertyNameCollectionFactory: $propertyNameCollectionFactory->reveal(),
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Dummy::class)->willReturn(true);

        $this->schemaFactory = new SchemaFactory(
            schemaFactory: $baseSchemaFactory,
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            resourceClassResolver: $resourceClassResolver->reveal(),
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );
    }

    public function testBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);

        $this->assertTrue($resultSchema->isDefined());
        $this->assertSame('Dummy.jsonapi', $resultSchema->getRootDefinitionKey());
    }

    public function testCustomFormatBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi');

        $this->assertTrue($resultSchema->isDefined());
        $this->assertSame('Dummy.jsonapi', $resultSchema->getRootDefinitionKey());
    }

    public function testHasRootDefinitionKeyBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        $this->assertTrue(isset($definitions[$rootDefinitionKey]['properties']));
        $properties = $resultSchema['definitions'][$rootDefinitionKey]['properties'];
        $this->assertArrayHasKey('data', $properties);
        $this->assertEquals(
            [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'string',
                    ],
                    'type' => [
                        'type' => 'string',
                    ],
                    'attributes' => [
                        '$ref' => '#/definitions/Dummy',
                    ],
                ],
                'required' => [
                    'type',
                    'id',
                ],
            ],
            $properties['data']
        );
    }

    public function testSchemaTypeBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_OUTPUT, new GetCollection());

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertTrue(isset($resultSchema['allOf'][0]['$ref']));
        $this->assertEquals($resultSchema['allOf'][0]['$ref'], '#/definitions/JsonApiCollectionBaseSchema');

        $jsonApiCollectionBaseSchema = $resultSchema['definitions']['JsonApiCollectionBaseSchema'];
        $this->assertTrue(isset($jsonApiCollectionBaseSchema['properties']));
        $this->assertArrayHasKey('links', $jsonApiCollectionBaseSchema['properties']);
        $this->assertArrayHasKey('self', $jsonApiCollectionBaseSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('first', $jsonApiCollectionBaseSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('prev', $jsonApiCollectionBaseSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('next', $jsonApiCollectionBaseSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('last', $jsonApiCollectionBaseSchema['properties']['links']['properties']);

        $this->assertArrayHasKey('meta', $jsonApiCollectionBaseSchema['properties']);
        $this->assertArrayHasKey('totalItems', $jsonApiCollectionBaseSchema['properties']['meta']['properties']);
        $this->assertArrayHasKey('itemsPerPage', $jsonApiCollectionBaseSchema['properties']['meta']['properties']);
        $this->assertArrayHasKey('currentPage', $jsonApiCollectionBaseSchema['properties']['meta']['properties']);

        $objectSchema = $resultSchema['allOf'][1];
        $this->assertArrayHasKey('data', $objectSchema['properties']);

        $this->assertArrayHasKey('items', $objectSchema['properties']['data']);
        $this->assertArrayHasKey('$ref', $objectSchema['properties']['data']['items']['properties']['attributes']);

        $properties = $objectSchema['properties'];
        $this->assertArrayHasKey('data', $properties);
        $this->assertArrayHasKey('items', $properties['data']);
        $this->assertArrayHasKey('id', $properties['data']['items']['properties']);
        $this->assertArrayHasKey('type', $properties['data']['items']['properties']);
        $this->assertArrayHasKey('attributes', $properties['data']['items']['properties']);

        $forcedCollection = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_OUTPUT, forceCollection: true);
        $this->assertEquals($resultSchema['allOf'][0]['$ref'], $forcedCollection['allOf'][0]['$ref']);
    }
}
