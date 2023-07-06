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

namespace ApiPlatform\JsonSchema\Tests;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\Tests\Fixtures\ApiResource\OverriddenOperationDummy;
use ApiPlatform\JsonSchema\Tests\Fixtures\DummyResourceInterface;
use ApiPlatform\JsonSchema\Tests\Fixtures\Enum\GenderTypeEnum;
use ApiPlatform\JsonSchema\Tests\Fixtures\NotAResource;
use ApiPlatform\JsonSchema\Tests\Fixtures\NotAResourceWithUnionIntersectTypes;
use ApiPlatform\JsonSchema\Tests\Fixtures\Serializable;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class SchemaFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testBuildSchemaForNonResourceClass(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(NotAResource::class, Argument::cetera())->willReturn(new PropertyNameCollection(['foo', 'bar', 'genderType']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'foo', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withReadable(true)
                ->withSchema(['type' => 'string'])
        );
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'bar', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])
                ->withReadable(true)
                ->withDefault('default_bar')
                ->withExample('example_bar')
                ->withSchema(['type' => 'integer', 'default' => 'default_bar', 'example' => 'example_bar'])
        );
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'genderType', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT)])
                ->withReadable(true)
                ->withDefault('male')
                ->withSchema(['type' => 'object', 'default' => 'male', 'example' => 'male'])
        );

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);

        $schemaFactory = new SchemaFactory(null, $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(NotAResource::class);

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(NotAResource::class))->getShortName(), $rootDefinitionKey);
        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['type']);
        $this->assertArrayNotHasKey('additionalProperties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('foo', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertArrayNotHasKey('default', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertArrayNotHasKey('example', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['foo']['type']);
        $this->assertArrayHasKey('bar', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertArrayHasKey('default', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertArrayHasKey('example', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertSame('integer', $definitions[$rootDefinitionKey]['properties']['bar']['type']);
        $this->assertSame('default_bar', $definitions[$rootDefinitionKey]['properties']['bar']['default']);
        $this->assertSame('example_bar', $definitions[$rootDefinitionKey]['properties']['bar']['example']);

        $this->assertArrayHasKey('genderType', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['genderType']);
        $this->assertArrayHasKey('default', $definitions[$rootDefinitionKey]['properties']['genderType']);
        $this->assertArrayHasKey('example', $definitions[$rootDefinitionKey]['properties']['genderType']);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['properties']['genderType']['type']);
        $this->assertSame('male', $definitions[$rootDefinitionKey]['properties']['genderType']['default']);
        $this->assertSame('male', $definitions[$rootDefinitionKey]['properties']['genderType']['example']);
    }

    public function testBuildSchemaForNonResourceClassWithUnionIntersectTypes(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(NotAResourceWithUnionIntersectTypes::class, Argument::cetera())->willReturn(new PropertyNameCollection(['ignoredProperty', 'unionType', 'intersectType']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(NotAResourceWithUnionIntersectTypes::class, 'ignoredProperty', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING, nullable: true)])
                ->withReadable(true)
                ->withSchema(['type' => ['string', 'null']])
        );
        $propertyMetadataFactoryProphecy->create(NotAResourceWithUnionIntersectTypes::class, 'unionType', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING, nullable: true), new Type(Type::BUILTIN_TYPE_INT, nullable: true), new Type(Type::BUILTIN_TYPE_FLOAT, nullable: true)])
                ->withReadable(true)
                ->withSchema(['oneOf' => [
                    ['type' => ['string', 'null']],
                    ['type' => ['integer', 'null']],
                ]])
        );
        $propertyMetadataFactoryProphecy->create(NotAResourceWithUnionIntersectTypes::class, 'intersectType', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, class: Serializable::class), new Type(Type::BUILTIN_TYPE_OBJECT, class: DummyResourceInterface::class)])
                ->withReadable(true)
                ->withSchema(['type' => 'object'])
        );

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(NotAResourceWithUnionIntersectTypes::class)->willReturn(false);

        $schemaFactory = new SchemaFactory(null, $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(NotAResourceWithUnionIntersectTypes::class);

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(NotAResourceWithUnionIntersectTypes::class))->getShortName(), $rootDefinitionKey);
        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['type']);
        $this->assertArrayNotHasKey('additionalProperties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);

        $this->assertArrayHasKey('ignoredProperty', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['ignoredProperty']);
        $this->assertSame(['string', 'null'], $definitions[$rootDefinitionKey]['properties']['ignoredProperty']['type']);

        $this->assertArrayHasKey('unionType', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('oneOf', $definitions[$rootDefinitionKey]['properties']['unionType']);
        $this->assertCount(2, $definitions[$rootDefinitionKey]['properties']['unionType']['oneOf']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['unionType']['oneOf'][0]);
        $this->assertSame(['string', 'null'], $definitions[$rootDefinitionKey]['properties']['unionType']['oneOf'][0]['type']);
        $this->assertSame(['integer', 'null'], $definitions[$rootDefinitionKey]['properties']['unionType']['oneOf'][1]['type']);

        $this->assertArrayHasKey('intersectType', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['intersectType']);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['properties']['intersectType']['type']);
    }

    public function testBuildSchemaWithSerializerGroups(): void
    {
        $shortName = (new \ReflectionClass(OverriddenOperationDummy::class))->getShortName();
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $operation = (new Put())->withName('put')->withNormalizationContext([
            'groups' => 'overridden_operation_dummy_put',
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
        ])->withShortName($shortName)->withValidationContext(['groups' => ['validation_groups_dummy_put']]);
        $resourceMetadataFactoryProphecy->create(OverriddenOperationDummy::class)
            ->willReturn(
                new ResourceMetadataCollection(OverriddenOperationDummy::class, [
                    (new ApiResource())->withOperations(new Operations(['put' => $operation])),
                ])
            );

        $serializerGroup = 'custom_operation_dummy';

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(OverriddenOperationDummy::class, Argument::type('array'))->willReturn(new PropertyNameCollection(['alias', 'description', 'genderType']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(OverriddenOperationDummy::class, 'alias', Argument::type('array'))->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withReadable(true)
                ->withSchema(['type' => 'string'])
        );
        $propertyMetadataFactoryProphecy->create(OverriddenOperationDummy::class, 'description', Argument::type('array'))->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])
                ->withReadable(true)
                ->withSchema(['type' => 'string'])
        );
        $propertyMetadataFactoryProphecy->create(OverriddenOperationDummy::class, 'genderType', Argument::type('array'))->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_OBJECT, false, GenderTypeEnum::class)])
                ->withReadable(true)
                ->withDefault(GenderTypeEnum::MALE)
                ->withSchema(['type' => 'object'])
        );

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(OverriddenOperationDummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(GenderTypeEnum::class)->willReturn(true);

        $schemaFactory = new SchemaFactory(null, $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(OverriddenOperationDummy::class, 'json', Schema::TYPE_OUTPUT, null, null, ['groups' => $serializerGroup, AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false]);

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(OverriddenOperationDummy::class))->getShortName().'-'.$serializerGroup, $rootDefinitionKey);
        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['type']);
        $this->assertFalse($definitions[$rootDefinitionKey]['additionalProperties']);
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('alias', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['alias']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['alias']['type']);
        $this->assertArrayHasKey('description', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['description']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['description']['type']);
        $this->assertArrayHasKey('genderType', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['genderType']);
        $this->assertArrayNotHasKey('default', $definitions[$rootDefinitionKey]['properties']['genderType']);
        $this->assertArrayNotHasKey('example', $definitions[$rootDefinitionKey]['properties']['genderType']);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['properties']['genderType']['type']);
    }

    public function testBuildSchemaForAssociativeArray(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(NotAResource::class, Argument::cetera())->willReturn(new PropertyNameCollection(['foo', 'bar']));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'foo', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING))])
                ->withReadable(true)
                ->withSchema(['type' => 'array', 'items' => ['string', 'int']])
        );
        $propertyMetadataFactoryProphecy->create(NotAResource::class, 'bar', Argument::cetera())->willReturn(
            (new ApiProperty())
                ->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_STRING), new Type(Type::BUILTIN_TYPE_STRING))])
                ->withReadable(true)
                ->withSchema(['type' => 'object', 'additionalProperties' => 'string'])
        );

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(NotAResource::class)->willReturn(false);

        $schemaFactory = new SchemaFactory(null, $resourceMetadataFactoryProphecy->reveal(), $propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), null, $resourceClassResolverProphecy->reveal());
        $resultSchema = $schemaFactory->buildSchema(NotAResource::class);

        $rootDefinitionKey = $resultSchema->getRootDefinitionKey();
        $definitions = $resultSchema->getDefinitions();

        $this->assertSame((new \ReflectionClass(NotAResource::class))->getShortName(), $rootDefinitionKey);
        // @noRector
        $this->assertTrue(isset($definitions[$rootDefinitionKey]));
        $this->assertArrayHasKey('properties', $definitions[$rootDefinitionKey]);
        $this->assertArrayHasKey('foo', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertArrayNotHasKey('additionalProperties', $definitions[$rootDefinitionKey]['properties']['foo']);
        $this->assertSame('array', $definitions[$rootDefinitionKey]['properties']['foo']['type']);
        $this->assertArrayHasKey('bar', $definitions[$rootDefinitionKey]['properties']);
        $this->assertArrayHasKey('type', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertArrayHasKey('additionalProperties', $definitions[$rootDefinitionKey]['properties']['bar']);
        $this->assertSame('object', $definitions[$rootDefinitionKey]['properties']['bar']['type']);
        $this->assertSame('string', $definitions[$rootDefinitionKey]['properties']['bar']['additionalProperties']);
    }
}
