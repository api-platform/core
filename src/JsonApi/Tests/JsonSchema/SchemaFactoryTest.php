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
            ]));
        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, ['enable_getter_setter_extraction' => true, 'schema_type' => Schema::TYPE_OUTPUT])->willReturn(new PropertyNameCollection());
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $definitionNameFactory = new DefinitionNameFactory(['jsonapi' => true]);

        $baseSchemaFactory = new BaseSchemaFactory(
            typeFactory: null,
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            propertyNameCollectionFactory: $propertyNameCollectionFactory->reveal(),
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );

        // $halSchemaFactory = new HalSchemaFactory($baseSchemaFactory);
        // $hydraSchemaFactory = new HydraSchemaFactory($halSchemaFactory);

        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);

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

        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        // @noRector
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
                        'properties' => [
                        ],
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
        $definitionName = 'Dummy.jsonapi';

        $this->assertNull($resultSchema->getRootDefinitionKey());
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']));
        $this->assertArrayHasKey('links', $resultSchema['properties']);
        $this->assertArrayHasKey('self', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('first', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('prev', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('next', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('last', $resultSchema['properties']['links']['properties']);

        $this->assertArrayHasKey('meta', $resultSchema['properties']);
        $this->assertArrayHasKey('totalItems', $resultSchema['properties']['meta']['properties']);
        $this->assertArrayHasKey('itemsPerPage', $resultSchema['properties']['meta']['properties']);
        $this->assertArrayHasKey('currentPage', $resultSchema['properties']['meta']['properties']);

        $this->assertArrayHasKey('data', $resultSchema['properties']);
        $this->assertArrayHasKey('items', $resultSchema['properties']['data']);
        $this->assertArrayHasKey('$ref', $resultSchema['properties']['data']['items']);

        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayHasKey('data', $properties);
        $this->assertArrayHasKey('properties', $properties['data']);
        $this->assertArrayHasKey('id', $properties['data']['properties']);
        $this->assertArrayHasKey('type', $properties['data']['properties']);
        $this->assertArrayHasKey('attributes', $properties['data']['properties']);

        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonapi', Schema::TYPE_OUTPUT, forceCollection: true);

        $this->assertNull($resultSchema->getRootDefinitionKey());
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']));
        $this->assertArrayHasKey('links', $resultSchema['properties']);
        $this->assertArrayHasKey('self', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('first', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('prev', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('next', $resultSchema['properties']['links']['properties']);
        $this->assertArrayHasKey('last', $resultSchema['properties']['links']['properties']);

        $this->assertArrayHasKey('meta', $resultSchema['properties']);
        $this->assertArrayHasKey('totalItems', $resultSchema['properties']['meta']['properties']);
        $this->assertArrayHasKey('itemsPerPage', $resultSchema['properties']['meta']['properties']);
        $this->assertArrayHasKey('currentPage', $resultSchema['properties']['meta']['properties']);

        $this->assertArrayHasKey('data', $resultSchema['properties']);
        $this->assertArrayHasKey('items', $resultSchema['properties']['data']);
        $this->assertArrayHasKey('$ref', $resultSchema['properties']['data']['items']);

        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayHasKey('data', $properties);
        $this->assertArrayHasKey('properties', $properties['data']);
        $this->assertArrayHasKey('id', $properties['data']['properties']);
        $this->assertArrayHasKey('type', $properties['data']['properties']);
        $this->assertArrayHasKey('attributes', $properties['data']['properties']);
    }
}
