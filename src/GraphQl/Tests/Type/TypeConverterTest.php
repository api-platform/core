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

namespace ApiPlatform\GraphQl\Tests\Type;

use ApiPlatform\GraphQl\Tests\Fixtures\Enum\GenderTypeEnum;
use ApiPlatform\GraphQl\Tests\Fixtures\Type\Definition\DateTimeType;
use ApiPlatform\GraphQl\Type\TypeBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\TypeConverter;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypeConverterTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $typeBuilderProphecy;
    private ObjectProphecy $typesContainerProphecy;
    private ObjectProphecy $resourceMetadataCollectionFactoryProphecy;
    private ObjectProphecy $propertyMetadataFactoryProphecy;
    private TypeConverter $typeConverter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->typeBuilderProphecy = $this->prophesize(TypeBuilderEnumInterface::class);
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->typeConverter = new TypeConverter($this->typeBuilderProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal());
    }

    /**
     * @dataProvider convertTypeProvider
     */
    public function testConvertType(Type $type, bool $input, int $depth, GraphQLType|string|null $expectedGraphqlType): void
    {
        $this->typeBuilderProphecy->isCollection($type)->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create(Argument::type('string'))->willThrow(new ResourceClassNotFoundException());
        $this->typeBuilderProphecy->getEnumType(Argument::type(Operation::class))->willReturn($expectedGraphqlType);

        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, $input, $operation, 'resourceClass', 'rootClass', null, $depth);
        $this->assertSame($expectedGraphqlType, $graphqlType);
    }

    public static function convertTypeProvider(): array
    {
        return [
            [new Type(Type::BUILTIN_TYPE_BOOL), false, 0, GraphQLType::boolean()],
            [new Type(Type::BUILTIN_TYPE_INT), false, 0, GraphQLType::int()],
            [new Type(Type::BUILTIN_TYPE_FLOAT), false, 0, GraphQLType::float()],
            [new Type(Type::BUILTIN_TYPE_STRING), false, 0, GraphQLType::string()],
            [new Type(Type::BUILTIN_TYPE_ARRAY), false, 0, 'Iterable'],
            [new Type(Type::BUILTIN_TYPE_ITERABLE), false, 0, 'Iterable'],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeInterface::class), false, 0, GraphQLType::string()],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, GenderTypeEnum::class), false, 0, new EnumType(['name' => 'GenderTypeEnum', 'values' => []])],
            [new Type(Type::BUILTIN_TYPE_OBJECT), false, 0, null],
            [new Type(Type::BUILTIN_TYPE_CALLABLE), false, 0, null],
            [new Type(Type::BUILTIN_TYPE_NULL), false, 0, null],
            [new Type(Type::BUILTIN_TYPE_RESOURCE), false, 0, null],
        ];
    }

    public function testConvertTypeNoGraphQlResourceMetadata(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create('dummy')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('dummy', [new ApiResource()]));

        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, false, $operation, 'resourceClass', 'rootClass', null, 0);
        $this->assertNull($graphqlType);
    }

    public function testConvertTypeNodeResource(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'node');

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create('node')->shouldBeCalled()->willReturn(new ResourceMetadataCollection('node', [(new ApiResource())->withShortName('Node')->withGraphQlOperations(['test' => new Query()])]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('A "Node" resource cannot be used with GraphQL because the type is already used by the Relay specification.');

        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $this->typeConverter->convertType($type, false, $operation, 'resourceClass', 'rootClass', null, 0);
    }

    public function testConvertTypeResourceClassNotFound(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create('dummy')->shouldBeCalled()->willThrow(new ResourceClassNotFoundException());

        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, false, $operation, 'resourceClass', 'rootClass', null, 0);
        $this->assertNull($graphqlType);
    }

    public function testConvertTypeResourceIri(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');

        $graphqlResourceMetadata = new ResourceMetadataCollection('dummy', [(new ApiResource())->withGraphQlOperations(['test' => new Query()])]);
        $this->resourceMetadataCollectionFactoryProphecy->create('dummy')->willReturn($graphqlResourceMetadata);
        $this->typeBuilderProphecy->isCollection($type)->willReturn(false);
        $this->propertyMetadataFactoryProphecy->create('rootClass', 'dummyProperty', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withWritableLink(false));

        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, true, $operation, 'dummy', 'rootClass', 'dummyProperty', 1);
        $this->assertSame(GraphQLType::string(), $graphqlType);
    }

    public function testConvertTypeInputResource(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');
        /** @var Operation $operation */
        $operation = new Query();
        $graphqlResourceMetadata = new ResourceMetadataCollection('dummy', [(new ApiResource())->withGraphQlOperations(['item_query' => $operation])]);
        $expectedGraphqlType = new ObjectType(['name' => 'resourceObjectType', 'fields' => []]);

        $this->resourceMetadataCollectionFactoryProphecy->create('dummy')->willReturn($graphqlResourceMetadata);
        $this->typeBuilderProphecy->isCollection($type)->willReturn(false);
        $this->propertyMetadataFactoryProphecy->create('rootClass', 'dummyProperty', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withWritableLink(true));
        $this->typeBuilderProphecy->getResourceObjectType('dummy', $graphqlResourceMetadata, $operation, true, false, 1)->shouldBeCalled()->willReturn($expectedGraphqlType);

        $graphqlType = $this->typeConverter->convertType($type, true, $operation, 'dummy', 'rootClass', 'dummyProperty', 1);
        $this->assertSame($expectedGraphqlType, $graphqlType);
    }

    /**
     * @dataProvider convertTypeResourceProvider
     */
    public function testConvertTypeCollectionResource(Type $type, ObjectType $expectedGraphqlType): void
    {
        $collectionOperation = new QueryCollection();
        $graphqlResourceMetadata = new ResourceMetadataCollection('dummyValue', [
            (new ApiResource())->withShortName('DummyValue')->withGraphQlOperations(['collection_query' => $collectionOperation]),
        ]);

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(true);
        $this->resourceMetadataCollectionFactoryProphecy->create('dummyValue')->shouldBeCalled()->willReturn($graphqlResourceMetadata);
        $this->typeBuilderProphecy->getResourceObjectType('dummyValue', $graphqlResourceMetadata, $collectionOperation, false, false, 0)->shouldBeCalled()->willReturn($expectedGraphqlType);

        /** @var Operation $rootOperation */
        $rootOperation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, false, $rootOperation, 'resourceClass', 'rootClass', null, 0);
        $this->assertSame($expectedGraphqlType, $graphqlType);
    }

    public static function convertTypeResourceProvider(): array
    {
        return [
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummyValue')), new ObjectType(['name' => 'resourceObjectType', 'fields' => []])],
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummyValue')), new ObjectType(['name' => 'resourceObjectType', 'fields' => []])],
        ];
    }

    /**
     * @dataProvider resolveTypeProvider
     */
    public function testResolveType(string $type, string|GraphQLType $expectedGraphqlType): void
    {
        $this->typesContainerProphecy->has(\DateTime::class)->willReturn(true);
        $this->typesContainerProphecy->get(\DateTime::class)->willReturn(new DateTimeType());

        $this->assertEquals($expectedGraphqlType, $this->typeConverter->resolveType($type));
    }

    public static function resolveTypeProvider(): array
    {
        return [
            ['String', GraphQLType::string()],
            ['String!', GraphQLType::nonNull(GraphQLType::string())],
            ['Boolean', GraphQLType::boolean()],
            ['[Boolean]', GraphQLType::listOf(GraphQLType::boolean())],
            ['Int!', GraphQLType::nonNull(GraphQLType::int())],
            ['[Int!]', GraphQLType::listOf(GraphQLType::nonNull(GraphQLType::int()))],
            ['Float', GraphQLType::float()],
            ['[Float]!', GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::float()))],
            [\DateTime::class, new DateTimeType()],
            ['[DateTime!]!', GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull(new DateTimeType())))],
        ];
    }

    /**
     * @dataProvider resolveTypeInvalidProvider
     */
    public function testResolveTypeInvalid(string $type, string $expectedExceptionMessage): void
    {
        $this->typesContainerProphecy->has('UnknownType')->willReturn(false);

        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->typeConverter->resolveType($type);
    }

    public static function resolveTypeInvalidProvider(): array
    {
        return [
            ['float?', '"float?" is not a valid GraphQL type.'],
            ['UnknownType', 'The type "UnknownType" was not resolved.'],
            ['UnknownType!', 'The type "UnknownType!" was not resolved.'],
        ];
    }
}
