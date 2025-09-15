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

namespace ApiPlatform\Hal\Tests\JsonSchema;

use ApiPlatform\Hal\JsonSchema\SchemaFactory;
use ApiPlatform\Hal\Tests\Fixtures\Dummy;
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
use PHPUnit\Framework\TestCase;

class SchemaFactoryTest extends TestCase
{
    private SchemaFactory $schemaFactory;

    protected function setUp(): void
    {
        $resourceMetadataFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactory->method('create')
            ->with(Dummy::class)
            ->willReturn(
                new ResourceMetadataCollection(Dummy::class, [
                    (new ApiResource())->withOperations(new Operations([
                        'get' => (new Get())->withName('get'),
                    ])),
                ])
            );

        $propertyNameCollectionFactory = $this->createMock(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->method('create')
            ->with(Dummy::class, ['enable_getter_setter_extraction' => true, 'schema_type' => Schema::TYPE_OUTPUT])
            ->willReturn(new PropertyNameCollection());

        $propertyMetadataFactory = $this->createMock(PropertyMetadataFactoryInterface::class);

        $definitionNameFactory = new DefinitionNameFactory(['jsonapi' => true, 'jsonhal' => true, 'jsonld' => true]);

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactory,
            propertyNameCollectionFactory: $propertyNameCollectionFactory,
            propertyMetadataFactory: $propertyMetadataFactory,
            definitionNameFactory: $definitionNameFactory,
        );

        $this->schemaFactory = new SchemaFactory($baseSchemaFactory);
    }

    public function testBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);

        $this->assertTrue($resultSchema->isDefined());
        $this->assertSame('Dummy.jsonhal', $resultSchema->getRootDefinitionKey());
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
        $this->assertTrue(isset($definitions[$rootDefinitionKey]['properties']));
        $properties = $resultSchema['definitions'][$rootDefinitionKey]['properties'];
        $this->assertArrayHasKey('_links', $properties);
        $this->assertEquals(
            [
                'type' => 'object',
                'properties' => [
                    'self' => [
                        'type' => 'object',
                        'properties' => [
                            'href' => [
                                'type' => 'string',
                                'format' => 'iri-reference',
                            ],
                        ],
                    ],
                ],
            ],
            $properties['_links']
        );
    }

    public function testSchemaTypeBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonhal', Schema::TYPE_OUTPUT, new GetCollection());
        $definitionName = 'Dummy.jsonhal';

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertTrue(isset($resultSchema['properties']));
        $this->assertArrayHasKey('_embedded', $resultSchema['properties']);
        $this->assertArrayHasKey('totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('itemsPerPage', $resultSchema['properties']);
        $this->assertArrayHasKey('_links', $resultSchema['properties']);
        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayHasKey('_links', $properties);

        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonhal', Schema::TYPE_OUTPUT, null, null, null, true);

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertTrue(isset($resultSchema['properties']));
        $this->assertArrayHasKey('_embedded', $resultSchema['properties']);
        $this->assertArrayHasKey('totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('itemsPerPage', $resultSchema['properties']);
        $this->assertArrayHasKey('_links', $resultSchema['properties']);
        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayHasKey('_links', $properties);
    }
}
