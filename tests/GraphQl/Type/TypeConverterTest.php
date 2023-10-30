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

namespace ApiPlatform\Tests\GraphQl\Type;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\GraphQl\Type\TypeBuilderInterface;
use ApiPlatform\GraphQl\Type\TypeConverter;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\GraphQl\Type\Definition\DateTimeType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypeConverterTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy */
    private $typeBuilderProphecy;

    /** @var ObjectProphecy */
    private $typesContainerProphecy;

    /** @var ObjectProphecy */
    private $resourceMetadataCollectionFactoryProphecy;

    /** @var ObjectProphecy */
    private $propertyMetadataFactoryProphecy;

    /** @var TypeConverter */
    private $typeConverter;

    protected function setUp(): void
    {
        $this->typeBuilderProphecy = $this->prophesize(TypeBuilderInterface::class);
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->typeConverter = new TypeConverter($this->typeBuilderProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal());
    }

    /**
     * @dataProvider convertTypeProvider
     *
     * @param string|GraphQLType|null $expectedGraphqlType
     */
    public function testConvertType(Type $type, bool $input, int $depth, $expectedGraphqlType): void
    {
        $this->typeBuilderProphecy->isCollection($type)->willReturn(false);

        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, $input, $operation, 'resourceClass', 'rootClass', null, $depth);
        $this->assertEquals($expectedGraphqlType, $graphqlType);
    }

    public function convertTypeProvider(): array
    {
        return [
            [new Type(Type::BUILTIN_TYPE_BOOL), false, 0, GraphQLType::boolean()],
            [new Type(Type::BUILTIN_TYPE_INT), false, 0, GraphQLType::int()],
            [new Type(Type::BUILTIN_TYPE_FLOAT), false, 0, GraphQLType::float()],
            [new Type(Type::BUILTIN_TYPE_STRING), false, 0, GraphQLType::string()],
            [new Type(Type::BUILTIN_TYPE_ARRAY), false, 0, 'Iterable'],
            [new Type(Type::BUILTIN_TYPE_ITERABLE), false, 0, 'Iterable'],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeInterface::class), false, 0, GraphQLType::string()],
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
        $this->assertEquals(GraphQLType::string(), $graphqlType);
    }

    public function testConvertTypeInputResource(): void
    {
        $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummy');
        /** @var Operation $operation */
        $operation = new Query();
        $graphqlResourceMetadata = new ResourceMetadataCollection('dummy', [(new ApiResource())->withGraphQlOperations(['item_query' => $operation])]);
        $expectedGraphqlType = new ObjectType(['name' => 'resourceObjectType']);

        $this->resourceMetadataCollectionFactoryProphecy->create('dummy')->willReturn($graphqlResourceMetadata);
        $this->typeBuilderProphecy->isCollection($type)->willReturn(false);
        $this->propertyMetadataFactoryProphecy->create('rootClass', 'dummyProperty', Argument::type('array'))->shouldBeCalled()->willReturn((new ApiProperty())->withWritableLink(true));
        $this->typeBuilderProphecy->getResourceObjectType('dummy', $graphqlResourceMetadata, $operation, true, false, 1)->shouldBeCalled()->willReturn($expectedGraphqlType);

        $graphqlType = $this->typeConverter->convertType($type, true, $operation, 'dummy', 'rootClass', 'dummyProperty', 1);
        $this->assertEquals($expectedGraphqlType, $graphqlType);
    }

    /**
     * @dataProvider convertTypeResourceProvider
     */
    public function testConvertTypeCollectionResource(Type $type, ObjectType $expectedGraphqlType): void
    {
        /** @var Operation $operation */
        $operation = (new Query())->withName('test');
        $graphqlResourceMetadata = new ResourceMetadataCollection('dummyValue', [(new ApiResource())->withShortName('DummyValue')->withGraphQlOperations(['test' => $operation])]);

        $this->typeBuilderProphecy->isCollection($type)->shouldBeCalled()->willReturn(true);
        $this->resourceMetadataCollectionFactoryProphecy->create('dummyValue')->shouldBeCalled()->willReturn($graphqlResourceMetadata);
        $this->typeBuilderProphecy->getResourceObjectType('dummyValue', $graphqlResourceMetadata, $operation, false, false, 0)->shouldBeCalled()->willReturn($expectedGraphqlType);

        /** @var Operation $rootOperation */
        $rootOperation = (new Query())->withName('test');
        $graphqlType = $this->typeConverter->convertType($type, false, $rootOperation, 'resourceClass', 'rootClass', null, 0);
        $this->assertEquals($expectedGraphqlType, $graphqlType);
    }

    public function convertTypeResourceProvider(): array
    {
        return [
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummyValue')), new ObjectType(['name' => 'resourceObjectType'])],
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'dummyValue')), new ObjectType(['name' => 'resourceObjectType'])],
        ];
    }

    /**
     * @dataProvider resolveTypeProvider
     *
     * @param string|GraphQLType $expectedGraphqlType
     */
    public function testResolveType(string $type, $expectedGraphqlType): void
    {
        $this->typesContainerProphecy->has('DateTime')->willReturn(true);
        $this->typesContainerProphecy->get('DateTime')->willReturn(new DateTimeType());

        $this->assertEquals($expectedGraphqlType, $this->typeConverter->resolveType($type));
    }

    public function resolveTypeProvider(): array
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
            ['DateTime', new DateTimeType()],
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

    public function resolveTypeInvalidProvider(): array
    {
        return [
            ['float?', '"float?" is not a valid GraphQL type.'],
            ['UnknownType', 'The type "UnknownType" was not resolved.'],
            ['UnknownType!', 'The type "UnknownType!" was not resolved.'],
        ];
    }
}
