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

namespace ApiPlatform\Tests\Hal\JsonSchema;

use ApiPlatform\Hal\JsonSchema\SchemaFactory;
use ApiPlatform\Hydra\JsonSchema\SchemaFactory as HydraSchemaFactory;
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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
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

        $definitionNameFactory = new DefinitionNameFactory();

        $baseSchemaFactory = new BaseSchemaFactory(
            resourceMetadataFactory: $resourceMetadataFactory->reveal(),
            propertyNameCollectionFactory: $propertyNameCollectionFactory->reveal(),
            propertyMetadataFactory: $propertyMetadataFactory->reveal(),
            definitionNameFactory: $definitionNameFactory,
        );

        $hydraSchemaFactory = new HydraSchemaFactory($baseSchemaFactory);

        $this->schemaFactory = new SchemaFactory($hydraSchemaFactory);
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
        $this->assertTrue(isset($definitions[$rootDefinitionKey]['allOf'][0]['properties']));
        $properties = $resultSchema['definitions'][$rootDefinitionKey]['allOf'][0]['properties'];
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

    public function testCollection(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonhal', Schema::TYPE_OUTPUT, new GetCollection());
        $this->assertNull($resultSchema->getRootDefinitionKey());

        $this->assertTrue(isset($resultSchema['definitions']['Dummy.jsonhal']));
        $this->assertTrue(isset($resultSchema['definitions']['HalCollectionBaseSchema']));
        $this->assertTrue(isset($resultSchema['definitions']['Dummy.jsonhal']));

        foreach ($resultSchema['allOf'] as $schema) {
            if (isset($schema['$ref'])) {
                $this->assertEquals($schema['$ref'], '#/definitions/HalCollectionBaseSchema');
                continue;
            }

            $this->assertArrayHasKey('_embedded', $schema['properties']);
            $this->assertEquals('#/definitions/Dummy.jsonhal', $schema['properties']['_embedded']['additionalProperties']['items']['$ref']);
        }

        $forceCollectionSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonhal', Schema::TYPE_OUTPUT, null, null, null, true);
        $this->assertEquals($forceCollectionSchema, $resultSchema);
    }
}
