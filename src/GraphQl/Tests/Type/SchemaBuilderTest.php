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

use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\SchemaBuilder;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\GraphQl\Type\TypesFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SchemaBuilderTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $resourceNameCollectionFactoryProphecy;
    private ObjectProphecy $resourceMetadataCollectionFactoryProphecy;
    private ObjectProphecy $typesFactoryProphecy;
    private ObjectProphecy $typesContainerProphecy;
    private ObjectProphecy $fieldsBuilderProphecy;
    private SchemaBuilder $schemaBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->typesFactoryProphecy = $this->prophesize(TypesFactoryInterface::class);
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $this->schemaBuilder = new SchemaBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->typesFactoryProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->fieldsBuilderProphecy->reveal());
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testGetSchema(string $resourceClass, ResourceMetadataCollection $resourceMetadata, ObjectType $expectedQueryType, ?ObjectType $expectedMutationType, ?ObjectType $expectedSubscriptionType): void
    {
        $type = new StringType(['name' => 'MyType']);
        $this->typesFactoryProphecy->getTypes()->shouldBeCalled()->willReturn(['typeId' => $type]);
        $this->typesContainerProphecy->set('typeId', $type)->shouldBeCalled();
        $this->typesContainerProphecy->get('MyType')->willReturn($type);
        $typeFoo = new StringType(['name' => 'Foo']);
        $this->typesContainerProphecy->get('Foo')->willReturn(GraphQLType::listOf($typeFoo));
        $this->fieldsBuilderProphecy->getNodeQueryFields()->shouldBeCalled()->willReturn(['node_fields']);
        $this->fieldsBuilderProphecy->getItemQueryFields($resourceClass, Argument::that(static fn (Operation $arg): bool => 'item_query' === $arg->getName()), [])->willReturn(['query' => ['query_fields']]);
        $this->fieldsBuilderProphecy->getCollectionQueryFields($resourceClass, Argument::that(static fn (Operation $arg): bool => 'collection_query' === $arg->getName()), [])->willReturn(['query' => ['query_fields']]);
        $this->fieldsBuilderProphecy->getItemQueryFields($resourceClass, Argument::that(static fn (Operation $arg): bool => 'custom_item_query' === $arg->getName()), [])->willReturn(['custom_item_query' => ['custom_item_query_fields']]);
        $this->fieldsBuilderProphecy->getCollectionQueryFields($resourceClass, Argument::that(static fn (Operation $arg): bool => 'custom_collection_query' === $arg->getName()), [])->willReturn(['custom_collection_query' => ['custom_collection_query_fields']]);
        $this->fieldsBuilderProphecy->getMutationFields($resourceClass, Argument::that(static fn (Operation $arg): bool => 'mutation' === $arg->getName()))->willReturn(['mutation' => ['mutation_fields']]);
        $this->fieldsBuilderProphecy->getSubscriptionFields($resourceClass, Argument::that(static fn (Operation $arg): bool => 'update' === $arg->getName()))->willReturn(['subscription' => ['subscription_fields']]);

        $this->resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([$resourceClass]));
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $this->typesContainerProphecy->set('Query', $expectedQueryType)->shouldBeCalled();
        if ($expectedMutationType) {
            $this->typesContainerProphecy->set('Mutation', $expectedMutationType)->shouldBeCalled();
        }
        if ($expectedSubscriptionType) {
            $this->typesContainerProphecy->set('Subscription', $expectedSubscriptionType)->shouldBeCalled();
        }

        $schema = $this->schemaBuilder->getSchema();
        $this->assertEquals($expectedQueryType, $schema->getQueryType());
        $this->assertEquals($expectedMutationType, $schema->getMutationType());
        $this->assertEquals($expectedSubscriptionType, $schema->getSubscriptionType());
        $this->assertSame($type, $schema->getType('MyType'));
        $this->assertSame($typeFoo, $schema->getType('Foo'));
    }

    public static function schemaProvider(): array
    {
        return [
            'no graphql configuration' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [new ApiResource()]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                    ],
                ]), null, null,
            ],
            'item query' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['item_query' => new Query()])]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'query' => ['query_fields'],
                    ],
                ]), null, null,
            ],
            'collection query' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['collection_query' => new QueryCollection()])]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'query' => ['query_fields'],
                    ],
                ]), null, null,
            ],
            'custom item query' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['custom_item_query' => (new Query())->withResolver('item_query_resolver')->withName('custom_item_query')])]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'custom_item_query' => ['custom_item_query_fields'],
                    ],
                ]), null, null,
            ],
            'custom collection query' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['custom_collection_query' => (new QueryCollection())->withResolver('collection_query_resolver')->withName('custom_collection_query')])]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'custom_collection_query' => ['custom_collection_query_fields'],
                    ],
                ]), null, null,
            ],
            'mutation' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['mutation' => (new Mutation())->withName('mutation')])]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                    ],
                ]),
                new ObjectType([
                    'name' => 'Mutation',
                    'fields' => [
                        'mutation' => ['mutation_fields'],
                    ],
                ]),
                null,
            ],
            'subscription' => [$resourceClass = 'resourceClass', new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['update' => (new Subscription())->withName('update')->withMercure(true)])]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                    ],
                ]),
                null,
                new ObjectType([
                    'name' => 'Subscription',
                    'fields' => [
                        'subscription' => ['subscription_fields'],
                    ],
                ]),
            ],
        ];
    }
}
