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

namespace ApiPlatform\Core\Tests\Hydra\JsonSchema;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Hydra\JsonSchema\SchemaFactory;
use ApiPlatform\Core\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Core\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;

class SchemaFactoryTest extends TestCase
{
    private $schemaFactory;

    protected function setUp(): void
    {
        $typeFactory = $this->prophesize(TypeFactoryInterface::class);
        $resourceMetadataFactory = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactory->create(Dummy::class)->willReturn(new ResourceMetadata(Dummy::class));
        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, [])->willReturn(new PropertyNameCollection());
        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $baseSchemaFactory = new BaseSchemaFactory(
            $typeFactory->reveal(),
            $resourceMetadataFactory->reveal(),
            $propertyNameCollectionFactory->reveal(),
            $propertyMetadataFactory->reveal()
        );

        $this->schemaFactory = new SchemaFactory($baseSchemaFactory);
    }

    public function testBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);

        $this->assertTrue($resultSchema->isDefined());
        $this->assertEquals(Dummy::class.':jsonld', $resultSchema->getRootDefinitionKey());
    }

    public function testCustomFormatBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'json');

        $this->assertTrue($resultSchema->isDefined());
        $this->assertEquals(Dummy::class, $resultSchema->getRootDefinitionKey());
    }

    public function testHasRootDefinitionKeyBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);
        $definitions = $resultSchema->getDefinitions();
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();

        $this->assertEquals(Dummy::class.':jsonld', $rootDefinitionKey);
        $this->assertArrayHasKey($rootDefinitionKey, $definitions);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
    }

    public function testSchemaTypeBuildSchema(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', true, OperationType::COLLECTION);

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertArrayHasKey('properties', $resultSchema);
        $this->assertArrayHasKey('hydra:member', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:view', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:search', $resultSchema['properties']);

        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class, 'jsonld', true, null, null, null, null, true);

        $this->assertNull($resultSchema->getRootDefinitionKey());
        $this->assertArrayHasKey('properties', $resultSchema);
        $this->assertArrayHasKey('hydra:member', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:totalItems', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:view', $resultSchema['properties']);
        $this->assertArrayHasKey('hydra:search', $resultSchema['properties']);
    }
}
