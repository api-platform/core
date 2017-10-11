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

namespace ApiPlatform\Core\Tests\Bridge\Graphql\Type;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Bridge\Graphql\Resolver\CollectionResolverFactoryInterface;
use ApiPlatform\Core\Bridge\Graphql\Resolver\ItemResolverFactoryInterface;
use ApiPlatform\Core\Bridge\Graphql\Type\SchemaBuilder;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
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
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SchemaBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testGetSchemaNoResourceIdentifier()
    {
        $propertyMetadataMockBuilder = function () {
            return new PropertyMetadata();
        };
        $mockedSchemaBuilder = $this->mockSchemaBuilder($propertyMetadataMockBuilder, false);
        $mockedSchemaBuilder->getSchema();
    }

    public function testGetSchemaNullOperation()
    {
        $propertyMetadataMockBuilder = function () {
            return new PropertyMetadata();
        };
        $mockedSchemaBuilder = $this->mockSchemaBuilder($propertyMetadataMockBuilder, false, true);
        $this->assertEquals([], $mockedSchemaBuilder->getSchema()->getConfig()->getQuery()->getFields());
    }

    public function testGetSchemaResourceClassNotFound()
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata(
                new Type(
                    $builtinType,
                    false,
                    'item3' === $resourceClassName ? 'unknownResource' : $resourceClassName
                ),
                $builtinType.'Description',
                null,
                null,
                null,
                null,
                null,
                Type::BUILTIN_TYPE_INT === $builtinType
            );
        };
        $mockedSchemaBuilder = $this->mockSchemaBuilder($propertyMetadataMockBuilder, false);
        $schema = $mockedSchemaBuilder->getSchema();
        $queryFields = $schema->getConfig()->getQuery()->getFields();

        // objectProperty has been skipped.
        /** @var ObjectType $type */
        $type = $queryFields['itemShortName3']->getType();
        $this->assertArrayNotHasKey('objectProperty', array_keys($type->getFields()));
    }

    /**
     * @dataProvider paginationProvider
     */
    public function testGetSchema($paginationEnabled, $expected)
    {
        $propertyMetadataMockBuilder = function ($builtinType, $resourceClassName) {
            return new PropertyMetadata(
                new Type(
                    $builtinType,
                    false,
                    'item3' === $resourceClassName ? \DateTime::class : $resourceClassName,
                    Type::BUILTIN_TYPE_OBJECT === $builtinType && 'item3' !== $resourceClassName,
                    null,
                    Type::BUILTIN_TYPE_OBJECT === $builtinType ? new Type(Type::BUILTIN_TYPE_STRING, false, $resourceClassName) : null
                ),
                $builtinType.'Description',
                true,
                null,
                null,
                null,
                null
            );
        };
        $mockedSchemaBuilder = $this->mockSchemaBuilder($propertyMetadataMockBuilder, $paginationEnabled);
        $schema = $mockedSchemaBuilder->getSchema();
        $queryFields = $schema->getConfig()->getQuery()->getFields();

        $this->assertEquals([
            'itemShortName1',
            'itemShortName1s',
            'itemShortName2',
            'itemShortName2s',
            'itemShortName3',
            'itemShortName3s',
            'collectionShortName1',
            'collectionShortName1s',
            'collectionShortName2',
            'collectionShortName2s',
            'collectionShortName3',
            'collectionShortName3s',
        ], array_keys($queryFields));

        /** @var ObjectType $type */
        $type = $queryFields['itemShortName2']->getType();
        $resourceTypeFields = $type->getFields();
        $this->assertEquals(
            ['intProperty', 'floatProperty', 'stringProperty', 'boolProperty', 'objectProperty'],
            array_keys($resourceTypeFields)
        );

        // Types are equal because of the cache.
        /** @var ObjectType $type */
        $type = $queryFields['collectionShortName1']->getType();
        if ($paginationEnabled) {
            /** @var ObjectType $objectPropertyFieldType */
            $objectPropertyFieldType = $type->getFields()['objectProperty']->getType();
            $this->assertEquals($objectPropertyFieldType->name, 'collectionShortName1Connection');
            /** @var ListOfType $edgesType */
            $edgesType = $objectPropertyFieldType->getFields()['edges']->getType();
            $edgeType = $edgesType->getWrappedType();
            $this->assertEquals($edgeType->name, 'collectionShortName1Edge');
            $this->assertEquals($edgeType->getFields()['cursor']->getType(), GraphQLType::nonNull(GraphQLType::string()));
            $this->assertEquals(
                $type,
                $edgeType->getFields()['node']->getType()
            );
        } else {
            /** @var ListOfType $objectPropertyFieldType */
            $objectPropertyFieldType = $type->getFields()['objectProperty']->getType();
            $this->assertEquals(
                $type,
                $objectPropertyFieldType->getWrappedType()
            );
        }

        // DateTime is considered as a string instead of an object.
        /** @var ObjectType $type */
        $type = $queryFields['itemShortName3']->getType();
        /** @var ListOfType $objectPropertyFieldType */
        $objectPropertyFieldType = $type->getFields()['objectProperty']->getType();
        $this->assertEquals(GraphQLType::nonNull(GraphQLType::string()), $objectPropertyFieldType);
    }

    public function paginationProvider()
    {
        return [[true, null], [false, null]];
    }

    private function mockSchemaBuilder($propertyMetadataMockBuilder, bool $paginationEnabled, bool $nullOperation = false): SchemaBuilder
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $collectionResolverFactoryProphecy = $this->prophesize(CollectionResolverFactoryInterface::class);
        $itemResolverFactoryProphecy = $this->prophesize(ItemResolverFactoryInterface::class);
        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);

        $resourceClassNames = [];
        $resourceTypes = ['item', 'collection'];
        foreach ($resourceTypes as $resourceType) {
            for ($i = 1; $i <= 3; ++$i) {
                $resourceClassName = $resourceType.$i;
                $resourceClassNames[] = $resourceClassName;
                $resourceMetadata = new ResourceMetadata(
                    $resourceType.'ShortName'.$i,
                    $resourceType.'Description'.$i,
                    null,
                    $nullOperation ? null : [
                        'get' => ['method' => 'GET'],
                        'post' => ['method' => 'POST'],
                        'op' => ['method' => 'GET', 'controller' => 'controller.name'],
                    ],
                    $nullOperation ? null : [
                        'get' => ['method' => 'GET'],
                        'post' => ['method' => 'POST'],
                        'op' => ['method' => 'GET', 'controller' => 'controller.name'],
                    ]
                );
                $resourceMetadataFactoryProphecy->create($resourceClassName)->willReturn($resourceMetadata);
                $resourceMetadataFactoryProphecy->create('unknownResource')->willThrow(new ResourceClassNotFoundException());

                $propertyNames = [];
                foreach (Type::$builtinTypes as $builtinType) {
                    $propertyName = $builtinType.'Property';
                    $propertyNames[] = $propertyName;
                    $propertyMetadata = $propertyMetadataMockBuilder($builtinType, $resourceClassName);
                    $propertyMetadataFactoryProphecy->create($resourceClassName, $propertyName)->willReturn($propertyMetadata);
                }
                $propertyNameCollection = new PropertyNameCollection($propertyNames);
                $propertyNameCollectionFactoryProphecy->create($resourceClassName)->willReturn($propertyNameCollection);

                $identifiersExtractorProphecy->getIdentifiersFromResourceClass($resourceClassName)->willReturn(['intProperty']);
            }
        }
        $resourceNameCollection = new ResourceNameCollection($resourceClassNames);
        $resourceNameCollectionFactoryProphecy->create()->willReturn($resourceNameCollection);

        $collectionResolverFactoryProphecy->createCollectionResolver(Argument::cetera())->willReturn(function () {});
        $itemResolverFactoryProphecy->createItemResolver(Argument::cetera())->willReturn(function () {});

        return new SchemaBuilder(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceNameCollectionFactoryProphecy->reveal(),
            $resourceMetadataFactoryProphecy->reveal(),
            $collectionResolverFactoryProphecy->reveal(),
            $itemResolverFactoryProphecy->reveal(),
            $identifiersExtractorProphecy->reveal(),
            $paginationEnabled
        );
    }
}
