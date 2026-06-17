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
use ApiPlatform\JsonApi\Tests\Fixtures\OtherRelatedDummy;
use ApiPlatform\JsonApi\Tests\Fixtures\RelatedDummy;
use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\TypeInfo\Type;

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
        $propertyNameCollectionFactory->create(Dummy::class, ['enable_getter_setter_extraction' => true, 'schema_type' => Schema::TYPE_INPUT])->willReturn(new PropertyNameCollection());
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
                        'type' => 'object',
                        'properties' => [],
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

        $jsonApiCollectionBaseSchemaNoPagination = $resultSchema['definitions']['JsonApiCollectionBaseSchemaNoPagination'];
        $this->assertTrue(isset($jsonApiCollectionBaseSchemaNoPagination['properties']));
        $this->assertArrayHasKey('links', $jsonApiCollectionBaseSchemaNoPagination['properties']);
        $this->assertArrayHasKey('self', $jsonApiCollectionBaseSchemaNoPagination['properties']['links']['properties']);
        $this->assertArrayHasKey('meta', $jsonApiCollectionBaseSchemaNoPagination['properties']);
        $this->assertArrayHasKey('totalItems', $jsonApiCollectionBaseSchemaNoPagination['properties']['meta']['properties']);

        $jsonApiCollectionBaseSchema = $resultSchema['definitions']['JsonApiCollectionBaseSchema'];
        $this->assertArrayHasKey('allOf', $jsonApiCollectionBaseSchema);
        $this->assertSame(['$ref' => '#/definitions/JsonApiCollectionBaseSchemaNoPagination'], $jsonApiCollectionBaseSchema['allOf'][0]);
        $this->assertArrayHasKey('first', $jsonApiCollectionBaseSchema['allOf'][1]['properties']['links']['properties']);
        $this->assertArrayHasKey('prev', $jsonApiCollectionBaseSchema['allOf'][1]['properties']['links']['properties']);
        $this->assertArrayHasKey('next', $jsonApiCollectionBaseSchema['allOf'][1]['properties']['links']['properties']);
        $this->assertArrayHasKey('last', $jsonApiCollectionBaseSchema['allOf'][1]['properties']['links']['properties']);

        $this->assertArrayHasKey('meta', $jsonApiCollectionBaseSchema['allOf'][1]['properties']);
        $this->assertArrayHasKey('itemsPerPage', $jsonApiCollectionBaseSchema['allOf'][1]['properties']['meta']['properties']);
        $this->assertArrayHasKey('currentPage', $jsonApiCollectionBaseSchema['allOf'][1]['properties']['meta']['properties']);

        $objectSchema = $resultSchema['allOf'][1];
        $this->assertArrayHasKey('data', $objectSchema['properties']);

        $this->assertArrayHasKey('items', $objectSchema['properties']['data']);
        $this->assertArrayNotHasKey('$ref', $objectSchema['properties']['data']['items']['properties']['attributes']);
        $this->assertSame('object', $objectSchema['properties']['data']['items']['properties']['attributes']['type']);

        $properties = $objectSchema['properties'];
        $this->assertArrayHasKey('data', $properties);
        $this->assertArrayHasKey('items', $properties['data']);
        $this->assertArrayHasKey('id', $properties['data']['items']['properties']);
        $this->assertArrayHasKey('type', $properties['data']['items']['properties']);
        $this->assertArrayHasKey('attributes', $properties['data']['items']['properties']);

        $forcedCollection = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_OUTPUT, forceCollection: true);
        $this->assertEquals($resultSchema['allOf'][0]['$ref'], $forcedCollection['allOf'][0]['$ref']);
    }

    public function testPostInputSchemaDoesNotRequireId(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_INPUT, new Post());
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $this->assertTrue(isset($definitions[$rootDefinitionKey]['properties']['data']));
        $data = $definitions[$rootDefinitionKey]['properties']['data'];
        $this->assertArrayHasKey('required', $data);
        $this->assertSame(['type'], $data['required']);
    }

    public function testPatchInputSchemaRequiresId(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_INPUT, new Patch());
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $data = $definitions[$rootDefinitionKey]['properties']['data'];
        $this->assertSame(['type', 'id'], $data['required']);
    }

    public function testPostOutputSchemaRequiresId(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_OUTPUT, new Post());
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $data = $definitions[$rootDefinitionKey]['properties']['data'];
        $this->assertSame(['type', 'id'], $data['required']);
    }

    public function testRelationIsExcludedFromAttributes(): void
    {
        $schemaFactory = $this->buildSchemaFactoryWithRelation();
        $resultSchema = $schemaFactory->buildSchema(Dummy::class, 'jsonapi');

        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $dataProperties = $definitions[$rootDefinitionKey]['properties']['data']['properties'];

        $this->assertArrayHasKey('attributes', $dataProperties);
        $this->assertArrayNotHasKey('$ref', $dataProperties['attributes']);
        $this->assertSame('object', $dataProperties['attributes']['type']);

        $attributes = $dataProperties['attributes']['properties'];
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayNotHasKey('relatedDummy', $attributes, 'relations must not be documented as attributes');

        $this->assertArrayHasKey('_id', $attributes, 'id is exposed as _id in the JSON:API attributes');
        $this->assertArrayNotHasKey('id', $attributes);

        $this->assertArrayHasKey('relationships', $dataProperties);
        $this->assertArrayHasKey('relatedDummy', $dataProperties['relationships']['properties']);
    }

    public function testRelationshipLinkageRequiresTypeAndId(): void
    {
        $schemaFactory = $this->buildSchemaFactoryWithRelation();
        $resultSchema = $schemaFactory->buildSchema(Dummy::class, 'jsonapi');

        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $dataProperties = $definitions[$rootDefinitionKey]['properties']['data']['properties'];

        // a resource identifier object MUST contain type and id, @see https://jsonapi.org/format/#document-resource-identifier-objects
        $linkage = $dataProperties['relationships']['properties']['relatedDummy']['properties']['data']['oneOf'][1];
        $this->assertSame('object', $linkage['type']);
        $this->assertSame(['type', 'id'], $linkage['required']);
    }

    public function testIncludedListsAllPolymorphicRelationTargets(): void
    {
        $schemaFactory = $this->buildSchemaFactoryWithPolymorphicRelation();
        $resultSchema = $schemaFactory->buildSchema(Dummy::class, 'jsonapi');

        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $included = $definitions[$rootDefinitionKey]['properties']['included'];

        $refs = array_column($included['items']['anyOf'], '$ref');
        $this->assertContains('#/definitions/RelatedDummy.jsonapi', $refs);
        $this->assertContains('#/definitions/OtherRelatedDummy.jsonapi', $refs, 'every target of a polymorphic relation must be listed in included');
    }

    private function buildSchemaFactoryWithPolymorphicRelation(): SchemaFactory
    {
        $dummyOperation = (new Get())->withName('get')->withShortName('Dummy');
        $relatedOperation = (new Get())->withName('get')->withShortName('RelatedDummy');
        $otherRelatedOperation = (new Get())->withName('get')->withShortName('OtherRelatedDummy');

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withOperations(new Operations(['get' => $dummyOperation])),
            ])
        );
        $resourceMetadataFactory->create(RelatedDummy::class)->willReturn(
            new ResourceMetadataCollection(RelatedDummy::class, [
                (new ApiResource())->withOperations(new Operations(['get' => $relatedOperation])),
            ])
        );
        $resourceMetadataFactory->create(OtherRelatedDummy::class)->willReturn(
            new ResourceMetadataCollection(OtherRelatedDummy::class, [
                (new ApiResource())->withOperations(new Operations(['get' => $otherRelatedOperation])),
            ])
        );

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['id', 'name', 'relatedDummy']));
        $propertyNameCollectionFactory->create(RelatedDummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['id', 'name']));
        $propertyNameCollectionFactory->create(OtherRelatedDummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['id', 'label']));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::int())->withReadable(true)->withSchema(['type' => 'integer'])
        );
        $propertyMetadataFactory->create(Dummy::class, 'name', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::string())->withReadable(true)->withSchema(['type' => 'string'])
        );
        $propertyMetadataFactory->create(Dummy::class, 'relatedDummy', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::union(Type::object(RelatedDummy::class), Type::object(OtherRelatedDummy::class)))->withReadable(true)->withSchema(['type' => Schema::UNKNOWN_TYPE])
        );
        $propertyMetadataFactory->create(RelatedDummy::class, 'id', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::int())->withReadable(true)->withSchema(['type' => 'integer'])
        );
        $propertyMetadataFactory->create(RelatedDummy::class, 'name', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::string())->withReadable(true)->withSchema(['type' => 'string'])
        );
        $propertyMetadataFactory->create(OtherRelatedDummy::class, 'id', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::int())->withReadable(true)->withSchema(['type' => 'integer'])
        );
        $propertyMetadataFactory->create(OtherRelatedDummy::class, 'label', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::string())->withReadable(true)->withSchema(['type' => 'string'])
        );

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolver->isResourceClass(RelatedDummy::class)->willReturn(true);
        $resourceClassResolver->isResourceClass(OtherRelatedDummy::class)->willReturn(true);

        $definitionNameFactory = new DefinitionNameFactory(null);

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            propertyNameCollectionFactory: $propertyNameCollectionFactory->reveal(),
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            resourceClassResolver: $resourceClassResolver->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );

        return new SchemaFactory(
            schemaFactory: $baseSchemaFactory,
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            resourceClassResolver: $resourceClassResolver->reveal(),
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );
    }

    private function buildSchemaFactoryWithRelation(): SchemaFactory
    {
        $dummyOperation = (new Get())->withName('get')->withShortName('Dummy');
        $relatedOperation = (new Get())->withName('get')->withShortName('RelatedDummy');

        $resourceMetadataFactory = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withOperations(new Operations(['get' => $dummyOperation])),
            ])
        );
        $resourceMetadataFactory->create(RelatedDummy::class)->willReturn(
            new ResourceMetadataCollection(RelatedDummy::class, [
                (new ApiResource())->withOperations(new Operations(['get' => $relatedOperation])),
            ])
        );

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['id', 'name', 'relatedDummy']));
        $propertyNameCollectionFactory->create(RelatedDummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['id', 'name']));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactory->create(Dummy::class, 'id', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::int())->withReadable(true)->withSchema(['type' => 'integer'])
        );
        $propertyMetadataFactory->create(Dummy::class, 'name', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::string())->withReadable(true)->withSchema(['type' => 'string'])
        );
        $propertyMetadataFactory->create(Dummy::class, 'relatedDummy', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::object(RelatedDummy::class))->withReadable(true)->withSchema(['type' => Schema::UNKNOWN_TYPE])
        );
        $propertyMetadataFactory->create(RelatedDummy::class, 'id', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::int())->withReadable(true)->withSchema(['type' => 'integer'])
        );
        $propertyMetadataFactory->create(RelatedDummy::class, 'name', Argument::type('array'))->willReturn(
            (new ApiProperty())->withNativeType(Type::string())->withReadable(true)->withSchema(['type' => 'string'])
        );

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolver->isResourceClass(RelatedDummy::class)->willReturn(true);

        $definitionNameFactory = new DefinitionNameFactory(null);

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            propertyNameCollectionFactory: $propertyNameCollectionFactory->reveal(),
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            resourceClassResolver: $resourceClassResolver->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );

        return new SchemaFactory(
            schemaFactory: $baseSchemaFactory,
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            resourceClassResolver: $resourceClassResolver->reveal(),
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );
    }
}
