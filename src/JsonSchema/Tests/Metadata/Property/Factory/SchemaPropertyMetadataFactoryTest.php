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

namespace ApiPlatform\JsonSchema\Tests\Metadata\Property\Factory;

use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyWithCustomOpenApiContext;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyWithEnum;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyWithMixed;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyWithUnionTypeProperty;
use ApiPlatform\JsonSchema\Tests\Fixtures\Enum\IntEnumAsIdentifier;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;

class SchemaPropertyMetadataFactoryTest extends TestCase
{
    #[IgnoreDeprecations]
    public function testEnumLegacy(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped();
        }
        $this->expectUserDeprecationMessage('Since api_platform/metadata 4.2: The "builtinTypes" argument of "ApiPlatform\Metadata\ApiProperty" is deprecated, use "nativeType" instead.');
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(builtinTypes: [new LegacyType(builtinType: 'object', nullable: true, class: IntEnumAsIdentifier::class)]);
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithEnum::class, 'intEnumAsIdentifier')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithEnum::class, 'intEnumAsIdentifier');
        $this->assertEquals(['type' => ['integer', 'null'], 'enum' => [1, 2, null]], $apiProperty->getSchema());
    }

    public function testEnum(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(nativeType: Type::nullable(Type::enum(IntEnumAsIdentifier::class)));
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithEnum::class, 'intEnumAsIdentifier')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithEnum::class, 'intEnumAsIdentifier');
        $this->assertEquals(['type' => ['integer', 'null'], 'enum' => [1, 2, null]], $apiProperty->getSchema());
    }

    #[IgnoreDeprecations]
    public function testWithCustomOpenApiContextLegacy(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped();
        }
        $this->expectUserDeprecationMessage('Since api_platform/metadata 4.2: The "builtinTypes" argument of "ApiPlatform\Metadata\ApiProperty" is deprecated, use "nativeType" instead.');
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(
            builtinTypes: [new LegacyType(builtinType: 'object', nullable: true, class: IntEnumAsIdentifier::class)],
            openapiContext: ['type' => 'object', 'properties' => ['alpha' => ['type' => 'integer']]],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'acme')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'acme');
        $this->assertEquals([], $apiProperty->getSchema());
    }

    public function testWithCustomOpenApiContext(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(
            nativeType: Type::nullable(Type::enum(IntEnumAsIdentifier::class)),
            openapiContext: ['type' => 'object', 'properties' => ['alpha' => ['type' => 'integer']]],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'acme')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'acme');
        $this->assertEquals([], $apiProperty->getSchema());
    }

    #[IgnoreDeprecations]
    public function testWithCustomOpenApiContextWithoutTypeDefinitionLegacy(): void
    {
        if (!class_exists(LegacyType::class)) {
            $this->markTestSkipped();
        }
        $this->expectUserDeprecationMessage('Since api_platform/metadata 4.2: The "builtinTypes" argument of "ApiPlatform\Metadata\ApiProperty" is deprecated, use "nativeType" instead.');
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(
            openapiContext: ['description' => 'My description'],
            builtinTypes: [new LegacyType(builtinType: 'bool')],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'foo')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'foo');
        $this->assertEquals([
            'type' => 'boolean',
        ], $apiProperty->getSchema());

        $apiProperty = new ApiProperty(
            openapiContext: ['iris' => 'https://schema.org/Date'],
            builtinTypes: [new LegacyType(builtinType: 'object', class: \DateTimeImmutable::class)],
        );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'bar')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'bar');
        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
        ], $apiProperty->getSchema());
    }

    public function testWithCustomOpenApiContextWithoutTypeDefinition(): void
    {
        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty =
            new ApiProperty(
                openapiContext: ['description' => 'My description'],
                nativeType: Type::bool(),
            );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'foo')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'foo');
        $this->assertEquals([
            'type' => 'boolean',
        ], $apiProperty->getSchema());

        $apiProperty =
            new ApiProperty(
                openapiContext: ['iris' => 'https://schema.org/Date'],
                nativeType: Type::object(\DateTimeImmutable::class),
            );
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithCustomOpenApiContext::class, 'bar')->willReturn($apiProperty);
        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithCustomOpenApiContext::class, 'bar');
        $this->assertEquals([
            'type' => 'string',
            'format' => 'date-time',
        ], $apiProperty->getSchema());
    }

    public function testUnionTypeAnyOfIsArray(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) { // @phpstan-ignore-line symfony/property-info 6.4 is still allowed and this may be true
            $this->markTestSkipped('This test only supports type-info component');
        }

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(nativeType: Type::union(Type::string(), Type::int()));
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithUnionTypeProperty::class, 'unionProperty')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithUnionTypeProperty::class, 'unionProperty');

        $expectedSchema = [
            'anyOf' => [
                ['type' => 'integer'],
                ['type' => 'string'],
            ],
        ];

        $this->assertEquals($expectedSchema, $apiProperty->getSchema());
    }

    /**
     * @see https://github.com/api-platform/core/issues/8271
     */
    public function testRelationWithGenIdFalseIsEmbeddedInOutputSchema(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) { // @phpstan-ignore-line symfony/property-info 6.4 is still allowed and this may be true
            $this->markTestSkipped('This test only supports type-info component');
        }

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $apiProperty = (new ApiProperty(nativeType: Type::object(Dummy::class)))
            ->withReadableLink(false)
            ->withGenId(false);
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithEnum::class, 'relatedDummy')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithEnum::class, 'relatedDummy');

        // defers to SchemaFactory ($ref to embedded subschema) instead of an iri-reference string
        $this->assertSame(['type' => Schema::UNKNOWN_TYPE], $apiProperty->getSchema());
    }

    /**
     * A resource-typed relation on a non-resource parent with readableLink=false must be an iri-reference in the
     * output schema. The circular reference handler (see AbstractItemNormalizer::$defaultContext) converts the related
     * resource to an IRI when a cycle is detected (e.g. A → B[] → A), so isReadableLink must drive the schema
     * instead of the old "non-resource parents always embed" heuristic.
     */
    public function testRelationOnNonResourceParentFollowsReadableLinkInOutputSchema(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) { // @phpstan-ignore-line symfony/property-info 6.4 is still allowed and this may be true
            $this->markTestSkipped('This test only supports type-info component');
        }

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        // the parent (DummyWithEnum) is not a resource, the related class (Dummy) is
        $resourceClassResolver->method('isResourceClass')->willReturnCallback(static fn (string $class): bool => Dummy::class === $class);

        // readableLink=false: the relation should be an iri-reference string even on a non-resource parent
        $apiProperty = (new ApiProperty(nativeType: Type::object(Dummy::class)))
            ->withReadableLink(false);
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithEnum::class, 'relatedDummy')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithEnum::class, 'relatedDummy');

        $this->assertSame(['type' => 'string', 'format' => 'iri-reference', 'example' => 'https://example.com/'], $apiProperty->getSchema());
    }

    /**
     * A relation on a non-resource parent where the property type is also not a resource must still be embedded
     * (UNKNOWN_TYPE → resolved as $ref by SchemaFactory). The standard object normalizer embeds non-resource
     * objects and isReadableLink is irrelevant here, so the schema defers to SchemaFactory.
     */
    public function testNonResourceRelationOnNonResourceParentIsEmbeddedInOutputSchema(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) { // @phpstan-ignore-line symfony/property-info 6.4 is still allowed and this may be true
            $this->markTestSkipped('This test only supports type-info component');
        }

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        // neither the parent nor the property type is a resource
        $resourceClassResolver->method('isResourceClass')->willReturn(false);

        $apiProperty = (new ApiProperty(nativeType: Type::object(DummyWithEnum::class)))
            ->withReadableLink(false);
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithEnum::class, 'nested')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithEnum::class, 'nested');

        $this->assertSame(['type' => Schema::UNKNOWN_TYPE], $apiProperty->getSchema());
    }

    /**
     * Counterpart to the non-resource case: a non-readable-link relation on a *resource* parent still follows
     * readableLink and stays an iri-reference string — the non-resource guard must not widen to resources.
     */
    public function testRelationOnResourceParentStaysIriReference(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) { // @phpstan-ignore-line symfony/property-info 6.4 is still allowed and this may be true
            $this->markTestSkipped('This test only supports type-info component');
        }

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        // both the parent and the related class are resources
        $resourceClassResolver->method('isResourceClass')->willReturn(true);

        $apiProperty = (new ApiProperty(nativeType: Type::object(Dummy::class)))
            ->withReadableLink(false);
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(Dummy::class, 'relatedDummy')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(Dummy::class, 'relatedDummy');

        $this->assertSame(['type' => 'string', 'format' => 'iri-reference', 'example' => 'https://example.com/'], $apiProperty->getSchema());
    }

    public function testMixed(): void
    {
        if (!method_exists(PropertyInfoExtractor::class, 'getType')) { // @phpstan-ignore-line symfony/property-info 6.4 is still allowed and this may be true
            $this->markTestSkipped('This test only supports type-info component');
        }

        $resourceClassResolver = $this->createMock(ResourceClassResolverInterface::class);
        $apiProperty = new ApiProperty(nativeType: Type::mixed());
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithMixed::class, 'mixedProperty')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithMixed::class, 'mixedProperty');

        $this->assertEquals([
            'type' => ['string', 'null'],
        ], $apiProperty->getSchema());

        $apiProperty = new ApiProperty(nativeType: Type::array(Type::mixed()));
        $decorated = $this->createMock(PropertyMetadataFactoryInterface::class);
        $decorated->expects($this->once())->method('create')->with(DummyWithMixed::class, 'mixedArrayProperty')->willReturn($apiProperty);

        $schemaPropertyMetadataFactory = new SchemaPropertyMetadataFactory($resourceClassResolver, $decorated);
        $apiProperty = $schemaPropertyMetadataFactory->create(DummyWithMixed::class, 'mixedArrayProperty');

        $this->assertEquals([
            'type' => 'array',
            'items' => [
                'type' => ['string', 'null'],
            ],
        ], $apiProperty->getSchema());
    }
}
