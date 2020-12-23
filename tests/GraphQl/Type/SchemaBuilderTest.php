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

use ApiPlatform\Core\GraphQl\Type\FieldsBuilderInterface;
use ApiPlatform\Core\GraphQl\Type\SchemaBuilder;
use ApiPlatform\Core\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Core\GraphQl\Type\TypesFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class SchemaBuilderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy */
    private $resourceNameCollectionFactoryProphecy;

    /** @var ObjectProphecy */
    private $resourceMetadataFactoryProphecy;

    /** @var ObjectProphecy */
    private $typesFactoryProphecy;

    /** @var ObjectProphecy */
    private $typesContainerProphecy;

    /** @var ObjectProphecy */
    private $fieldsBuilderProphecy;

    /** @var SchemaBuilder */
    private $schemaBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->resourceNameCollectionFactoryProphecy = $this->prophesize(ResourceNameCollectionFactoryInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->typesFactoryProphecy = $this->prophesize(TypesFactoryInterface::class);
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $this->schemaBuilder = new SchemaBuilder($this->resourceNameCollectionFactoryProphecy->reveal(), $this->resourceMetadataFactoryProphecy->reveal(), $this->typesFactoryProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->fieldsBuilderProphecy->reveal());
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testGetSchema(string $resourceClass, ResourceMetadata $resourceMetadata, ObjectType $expectedQueryType, ?ObjectType $expectedMutationType, ?ObjectType $expectedSubscriptionType): void
    {
        $type = $this->prophesize(GraphQLType::class)->reveal();
        $type->name = 'MyType';
        $this->typesFactoryProphecy->getTypes()->shouldBeCalled()->willReturn(['typeId' => $type]);
        $this->typesContainerProphecy->set('typeId', $type)->shouldBeCalled();
        $this->typesContainerProphecy->get('MyType')->willReturn($type);
        $typeFoo = $this->prophesize(GraphQLType::class)->reveal();
        $typeFoo->name = 'Foo';
        $this->typesContainerProphecy->get('Foo')->willReturn(GraphQLType::listOf($typeFoo));
        $this->fieldsBuilderProphecy->getNodeQueryFields()->shouldBeCalled()->willReturn(['node_fields']);
        $this->fieldsBuilderProphecy->getItemQueryFields($resourceClass, $resourceMetadata, 'item_query', [])->willReturn(['query' => ['query_fields']]);
        $this->fieldsBuilderProphecy->getCollectionQueryFields($resourceClass, $resourceMetadata, 'collection_query', [])->willReturn(['query' => ['query_fields']]);
        $this->fieldsBuilderProphecy->getItemQueryFields($resourceClass, $resourceMetadata, 'custom_item_query', ['item_query' => 'item_query_resolver'])->willReturn(['custom_item_query' => ['custom_item_query_fields']]);
        $this->fieldsBuilderProphecy->getCollectionQueryFields($resourceClass, $resourceMetadata, 'custom_collection_query', ['collection_query' => 'collection_query_resolver'])->willReturn(['custom_collection_query' => ['custom_collection_query_fields']]);
        $this->fieldsBuilderProphecy->getMutationFields($resourceClass, $resourceMetadata, 'mutation')->willReturn(['mutation' => ['mutation_fields']]);
        $this->fieldsBuilderProphecy->getMutationFields($resourceClass, $resourceMetadata, 'update')->willReturn(['mutation' => ['mutation_fields']]);
        $this->fieldsBuilderProphecy->getSubscriptionFields($resourceClass, $resourceMetadata, 'update')->willReturn(['subscription' => ['subscription_fields']]);

        $this->resourceNameCollectionFactoryProphecy->create()->willReturn(new ResourceNameCollection([$resourceClass]));
        $this->resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($resourceMetadata);

        $schema = $this->schemaBuilder->getSchema();
        $this->assertEquals($expectedQueryType, $schema->getQueryType());
        $this->assertEquals($expectedMutationType, $schema->getMutationType());
        $this->assertEquals($expectedSubscriptionType, $schema->getSubscriptionType());
        $this->assertEquals($type, $schema->getType('MyType'));
        $this->assertEquals($typeFoo, $schema->getType('Foo'));
    }

    public function schemaProvider(): array
    {
        return [
            'no graphql configuration' => ['resourceClass', new ResourceMetadata(),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                    ],
                ]), null, null,
            ],
            'item query' => ['resourceClass', (new ResourceMetadata())->withGraphql(['item_query' => []]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'query' => ['query_fields'],
                    ],
                ]), null, null,
            ],
            'collection query' => ['resourceClass', (new ResourceMetadata())->withGraphql(['collection_query' => []]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'query' => ['query_fields'],
                    ],
                ]), null, null,
            ],
            'custom item query' => ['resourceClass', (new ResourceMetadata())->withGraphql(['custom_item_query' => ['item_query' => 'item_query_resolver']]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'custom_item_query' => ['custom_item_query_fields'],
                    ],
                ]), null, null,
            ],
            'custom collection query' => ['resourceClass', (new ResourceMetadata())->withGraphql(['custom_collection_query' => ['collection_query' => 'collection_query_resolver']]),
                new ObjectType([
                    'name' => 'Query',
                    'fields' => [
                        'node' => ['node_fields'],
                        'custom_collection_query' => ['custom_collection_query_fields'],
                    ],
                ]), null, null,
            ],
            'mutation' => ['resourceClass', (new ResourceMetadata())->withGraphql(['mutation' => []]),
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
            'subscription' => ['resourceClass', (new ResourceMetadata())->withGraphql(['update' => []]),
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
