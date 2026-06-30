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
use ApiPlatform\JsonSchema\SchemaFactory as BaseSchemaFactory;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operations;
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

class ReservedAttributeNameSchemaFactoryTest extends TestCase
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

        // A scalar property for every JSON:API reserved attribute name, plus a regular one.
        $propertyNames = ['id', 'type', 'links', 'relationships', 'included', 'name'];

        $propertyNameCollectionFactory = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactory->create(Dummy::class, Argument::any())->willReturn(new PropertyNameCollection($propertyNames));

        $propertyMetadataFactory = $this->prophesize(PropertyMetadataFactoryInterface::class);
        foreach ($propertyNames as $propertyName) {
            $propertyMetadataFactory->create(Dummy::class, $propertyName, Argument::any())->willReturn(
                (new ApiProperty())->withNativeType(Type::string())->withReadable(true)->withWritable(true)
            );
        }

        $definitionNameFactory = new DefinitionNameFactory();

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

    public function testReservedAttributeNamesAreRenamedLikeTheResponse(): void
    {
        $resultSchema = $this->schemaFactory->buildSchema(Dummy::class);
        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $attributes = $resultSchema['definitions'][$rootDefinitionKey]['properties']['data']['properties']['attributes']['properties'];

        // Every reserved name must be documented under the prefixed key the ReservedAttributeNameConverter emits.
        $this->assertArrayHasKey('_id', $attributes);
        $this->assertArrayHasKey('_type', $attributes);
        $this->assertArrayHasKey('_links', $attributes);
        $this->assertArrayHasKey('_relationships', $attributes);
        $this->assertArrayHasKey('_included', $attributes);
        $this->assertArrayHasKey('name', $attributes);

        // The bare reserved names must never leak: the response never emits them.
        $this->assertArrayNotHasKey('id', $attributes);
        $this->assertArrayNotHasKey('type', $attributes);
        $this->assertArrayNotHasKey('links', $attributes);
        $this->assertArrayNotHasKey('relationships', $attributes);
        $this->assertArrayNotHasKey('included', $attributes);
    }
}
