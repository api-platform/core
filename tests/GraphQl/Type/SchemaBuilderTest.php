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

namespace ApiPlatform\Core\Tests\GraphQl\Type;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\Core\GraphQl\Type\SchemaBuilder;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SchemaBuilderTest extends TestCase
{
    public function testGetSchemaAllFields()
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata(
                new Type(
                    $builtinType,
                    false,
                    'GraphqlResource3' === $resourceClassName ? 'unknownResource' : $resourceClassName
                ),
                "{$builtinType}Description",
                null,
                null,
                null,
                null,
                null,
                Type::BUILTIN_TYPE_INT === $builtinType
            );
        };

        $mockedSchemaBuilder = $this->createSchemaBuilder($propertyMetadataMockBuilder, false);
        $this->assertEquals([
            'node',
            'shortName1',
            'shortName1s',
            'shortName2',
            'shortName2s',
            'shortName3',
            'shortName3s',
        ], array_keys($mockedSchemaBuilder->getSchema()->getConfig()->getQuery()->getFields()));
    }

    public function testGetSchemaResourceClassNotFound()
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata(
                new Type(
                    $builtinType,
                    false,
                    'GraphqlResource3' === $resourceClassName ? 'unknownResource' : $resourceClassName
                ),
                "{$builtinType}Description",
                null,
                null,
                null,
                null,
                null,
                Type::BUILTIN_TYPE_INT === $builtinType
            );
        };
        $mockedSchemaBuilder = $this->createSchemaBuilder($propertyMetadataMockBuilder, false);
        $schema = $mockedSchemaBuilder->getSchema();
        $queryFields = $schema->getConfig()->getQuery()->getFields();

        // objectProperty has been skipped.
        /** @var ObjectType $type */
        $type = $queryFields['shortName3']->getType();
        $this->assertArrayNotHasKey('objectProperty', $type->getFields());
    }

    public function testConvertFilterArgsToTypes()
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata();
        };
        $mockedSchemaBuilder = $this->createSchemaBuilder($propertyMetadataMockBuilder, false);

        $reflectionClass = new \ReflectionClass(SchemaBuilder::class);
        $method = $reflectionClass->getMethod('convertFilterArgsToTypes');
        $method->setAccessible(true);
        $filterArgs = [
            'aField' => 'string',
            'GraphqlRelatedResource.nestedFieldA' => 'string',
            'GraphqlRelatedResource.nestedFieldB' => 'string',
        ];

        $this->assertSame(
            [
                'aField' => 'string',
                'GraphqlRelatedResource_nestedFieldA' => 'string',
                'GraphqlRelatedResource_nestedFieldB' => 'string',
            ],
            $method->invoke($mockedSchemaBuilder, $filterArgs)
        );
    }

    /**
     * @dataProvider paginationProvider
     */
    public function testGetSchema(bool $paginationEnabled)
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata(
                new Type(
                    $builtinType,
                    false,
                    'GraphqlResource3' === $resourceClassName ? \DateTime::class : $resourceClassName,
                    Type::BUILTIN_TYPE_OBJECT === $builtinType && 'GraphqlResource3' !== $resourceClassName,
                    null,
                    Type::BUILTIN_TYPE_OBJECT === $builtinType ? new Type(Type::BUILTIN_TYPE_STRING, false, $resourceClassName) : null
                ),
                "{$builtinType}Description",
                true,
                true,
                null,
                null,
                null
            );
        };
        $mockedSchemaBuilder = $this->createSchemaBuilder($propertyMetadataMockBuilder, $paginationEnabled);
        $schema = $mockedSchemaBuilder->getSchema();

        $queryFields = $schema->getConfig()->getQuery()->getFields();
        $mutationFields = $schema->getConfig()->getMutation()->getFields();

        $this->assertSame([
            'node',
            'shortName1',
            'shortName1s',
            'shortName2',
            'shortName2s',
            'shortName3',
            'shortName3s',
        ], array_keys($queryFields));

        $this->assertEquals([
            'createShortName1',
            'createShortName2',
            'createShortName3',
        ], array_keys($mutationFields));

        /** @var ObjectType $type */
        $type = $queryFields['shortName2']->getType();
        $resourceTypeFields = $type->getFields();
        $this->assertSame(
            ['id', '_id', 'floatProperty', 'stringProperty', 'boolProperty', 'objectProperty', 'arrayProperty', 'iterableProperty'],
            array_keys($resourceTypeFields)
        );

        // Types are equal because of the cache.
        /** @var ObjectType $type */
        $type = $queryFields['shortName1']->getType();
        if ($paginationEnabled) {
            /** @var ObjectType $objectPropertyFieldType */
            $objectPropertyFieldType = $type->getFields()['objectProperty']->getType();
            $this->assertSame('ShortName1Connection', $objectPropertyFieldType->name);
            $this->assertEquals(GraphQLType::nonNull(GraphQLType::int()), $objectPropertyFieldType->getField('totalCount')->getType());
            /** @var ListOfType $edgesType */
            $edgesType = $objectPropertyFieldType->getFields()['edges']->getType();
            /** @var ObjectType $edgeType */
            $edgeType = $edgesType->getWrappedType();
            $this->assertSame('ShortName1Edge', $edgeType->name);
            $this->assertEquals(GraphQLType::nonNull(GraphQLType::string()), $edgeType->getField('cursor')->getType());
            $this->assertEquals($type, $edgeType->getField('node')->getType());
        } else {
            /** @var ListOfType $objectPropertyFieldType */
            $objectPropertyFieldType = $type->getFields()['objectProperty']->getType();
            $this->assertEquals($type, $objectPropertyFieldType->getWrappedType());
        }

        // DateTime is considered as a string instead of an object.
        /** @var ObjectType $type */
        $type = $queryFields['shortName3']->getType();
        /** @var ListOfType $objectPropertyFieldType */
        $objectPropertyFieldType = $type->getField('objectProperty')->getType();
        $this->assertEquals(GraphQLType::nonNull(GraphQLType::string()), $objectPropertyFieldType);

        /** @var ObjectType $type */
        $type = $mutationFields['createShortName2']->getType();
        $resourceTypeFields = $type->getFields();
        $this->assertEquals(
            ['shortName2', 'clientMutationId'],
            array_keys($resourceTypeFields)
        );
    }

    /**
     * Tests that the GraphQL SchemaBuilder supports an edge case where a property is typed as an Type::BUILTIN_TYPE_OBJECT but has no class related.
     */
    public function testObjectTypeWithoutClass()
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata(
                new Type(
                    $builtinType
                ),
                "{$builtinType}Description",
                true,
                true,
                null,
                null,
                null
            );
        };

        $mockedSchemaBuilder = $this->createSchemaBuilder($propertyMetadataMockBuilder, false);
        $this->assertEquals([
            'node',
            'shortName1',
            'shortName1s',
            'shortName2',
            'shortName2s',
            'shortName3',
            'shortName3s',
        ], array_keys($mockedSchemaBuilder->getSchema()->getConfig()->getQuery()->getFields()));
    }

    public function paginationProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    private function createSchemaBuilder($propertyMetadataMockBuilder, bool $paginationEnabled): SchemaBuilder
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $collectionResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $itemMutationResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);

        $resourceClassNames = [];
        for ($i = 1; $i <= 3; ++$i) {
            $resourceClassName = "GraphqlResource$i";
            $resourceClassNames[] = $resourceClassName;
            $resourceMetadata = new ResourceMetadata(
                "ShortName$i",
                "Description$i",
                null,
                null,
                null,
                null,
                null,
                ['query' => [], 'create' => []]
            );
            $resourceMetadataFactoryProphecy->create($resourceClassName)->willReturn($resourceMetadata);
            $resourceMetadataFactoryProphecy->create('unknownResource')->willThrow(new ResourceClassNotFoundException());

            $propertyNames = [];
            foreach (Type::$builtinTypes as $builtinType) {
                $propertyName = Type::BUILTIN_TYPE_INT === $builtinType ? 'id' : "{$builtinType}Property";
                $propertyNames[] = $propertyName;
                $propertyMetadataFactoryProphecy->create($resourceClassName, $propertyName, ['graphql_operation_name' => 'query'])->willReturn($propertyMetadataMockBuilder($builtinType, $resourceClassName));
                $propertyMetadataFactoryProphecy->create($resourceClassName, $propertyName, ['graphql_operation_name' => 'create'])->willReturn($propertyMetadataMockBuilder($builtinType, $resourceClassName));
            }
            $propertyNameCollection = new PropertyNameCollection($propertyNames);
            $propertyNameCollectionFactoryProphecy->create($resourceClassName)->willReturn($propertyNameCollection);
        }
        $resourceNameCollection = new ResourceNameCollection($resourceClassNames);
        $resourceNameCollectionFactoryProphecy->create()->willReturn($resourceNameCollection);

        $collectionResolverFactoryProphecy->__invoke(Argument::cetera())->willReturn(function () {
        });
        $itemMutationResolverFactoryProphecy->__invoke(Argument::cetera())->willReturn(function () {
        });

        return new SchemaBuilder(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $collectionResolverFactoryProphecy->reveal(),
            $itemMutationResolverFactoryProphecy->reveal(),
            function () {
            },
            function () {
            },
            null,
            $paginationEnabled
        );
    }
}
