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

namespace ApiPlatform\Tests\Hydra\JsonSchema;

use ApiPlatform\Hydra\JsonSchema\SchemaFactory;
use ApiPlatform\JsonLd\ContextBuilder;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
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
        $typeFactory = $this->prophesize(TypeFactoryInterface::class);
        $resourceMetadataFactoryCollection = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryCollection->create(Dummy::class)->willReturn(
            new ResourceMetadataCollection(Dummy::class, [
                new ApiResource(operations: [
                    'get' => new Get(name: 'get'),
                ]),
            ])
        );

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, ['enable_getter_setter_extraction' => true])->willReturn(new PropertyNameCollection());
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $baseSchemaFactory = new BaseSchemaFactory(
            $typeFactory->reveal(),
            $resourceMetadataFactoryCollection->reveal(),
            $propertyNameCollectionFactory->reveal(),
            $propertyMetadataFactory->reveal()
        );

        $this->schemaFactory = new SchemaFactory($baseSchemaFactory);
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

        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]['properties']));
        $properties = $resultSchema['definitions'][$rootDefinitionKey]['properties'];
        $this->assertArrayHasKey('@context', $properties);
        $this->assertEquals(
            [
                'readOnly' => true,
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
                            '@language' => [
                                'type' => 'string',
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
        $definitionName = 'Dummy.jsonld';

        $this->assertNull($resultSchema->getRootDefinitionKey());
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']));
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']['hydra:member']));
        $this->assertArrayHasKey('hydra:totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:view', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:search', $resultSchema['properties']);
        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayNotHasKey('@context', $properties);
        $this->assertArrayHasKey('@type', $properties);
        $this->assertArrayHasKey('@id', $properties);

        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, null, null, null, true);

        $this->assertNull($resultSchema->getRootDefinitionKey());
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']));
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']['hydra:member']));
        $this->assertArrayHasKey('hydra:totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:view', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:search', $resultSchema['properties']);
        $properties = $resultSchema['definitions'][$definitionName]['properties'];
        $this->assertArrayNotHasKey('@context', $properties);
        $this->assertArrayHasKey('@type', $properties);
        $this->assertArrayHasKey('@id', $properties);
    }

    public function testHasHydraViewNavigationBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', Schema::TYPE_OUTPUT, new GetCollection());

        $this->assertNull($resultSchema->getRootDefinitionKey());
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']));
        // @noRector
        $this->assertTrue(isset($resultSchema['properties']['hydra:view']));
        $this->assertArrayHasKey('properties', $resultSchema['properties']['hydra:view']);
        $this->assertArrayHasKey('hydra:first', $resultSchema['properties']['hydra:view']['properties']);
        $this->assertArrayHasKey('hydra:last', $resultSchema['properties']['hydra:view']['properties']);
        $this->assertArrayHasKey('hydra:previous', $resultSchema['properties']['hydra:view']['properties']);
        $this->assertArrayHasKey('hydra:next', $resultSchema['properties']['hydra:view']['properties']);
    }
}
