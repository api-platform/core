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

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Metadata\Property\SubresourceMetadata;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\Type\FieldsBuilder;
use ApiPlatform\GraphQl\Type\TypeBuilderInterface;
use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\TestBundle\Serializer\NameConverter\CustomConverter;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class FieldsBuilderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy */
    private $propertyNameCollectionFactoryProphecy;

    /** @var ObjectProphecy */
    private $propertyMetadataFactoryProphecy;

    /** @var ObjectProphecy */
    private $resourceMetadataCollectionFactoryProphecy;

    /** @var ObjectProphecy */
    private $typesContainerProphecy;

    /** @var ObjectProphecy */
    private $typeBuilderProphecy;

    /** @var ObjectProphecy */
    private $typeConverterProphecy;

    /** @var ObjectProphecy */
    private $itemResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $collectionResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $itemMutationResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $itemSubscriptionResolverFactoryProphecy;

    /** @var ObjectProphecy */
    private $filterLocatorProphecy;

    /** @var FieldsBuilder */
    private $fieldsBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $this->propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $this->resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->typeBuilderProphecy = $this->prophesize(TypeBuilderInterface::class);
        $this->typeConverterProphecy = $this->prophesize(TypeConverterInterface::class);
        $this->itemResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->collectionResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->itemMutationResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->itemSubscriptionResolverFactoryProphecy = $this->prophesize(ResolverFactoryInterface::class);
        $this->filterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->fieldsBuilder = $this->buildFieldsBuilder();
    }

    private function buildFieldsBuilder(?AdvancedNameConverterInterface $advancedNameConverter = null): FieldsBuilder
    {
        return new FieldsBuilder($this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->typeBuilderProphecy->reveal(), $this->typeConverterProphecy->reveal(), $this->itemResolverFactoryProphecy->reveal(), $this->collectionResolverFactoryProphecy->reveal(), $this->itemMutationResolverFactoryProphecy->reveal(), $this->itemSubscriptionResolverFactoryProphecy->reveal(), $this->filterLocatorProphecy->reveal(), new Pagination($this->resourceMetadataCollectionFactoryProphecy->reveal()), $advancedNameConverter ?? new CustomConverter(), '__');
    }

    public function testGetNodeQueryFields(): void
    {
        $nodeInterfaceType = $this->prophesize(InterfaceType::class)->reveal();
        $this->typeBuilderProphecy->getNodeInterface()->shouldBeCalled()->willReturn($nodeInterfaceType);

        $itemResolver = function () {
        };
        $this->itemResolverFactoryProphecy->__invoke()->shouldBeCalled()->willReturn($itemResolver);

        $nodeQueryFields = $this->fieldsBuilder->getNodeQueryFields();
        $this->assertArrayHasKey('type', $nodeQueryFields);
        $this->assertArrayHasKey('args', $nodeQueryFields);
        $this->assertArrayHasKey('resolve', $nodeQueryFields);

        $this->assertSame($nodeInterfaceType, $nodeQueryFields['type']);
        $this->assertArrayHasKey('id', $nodeQueryFields['args']);
        $this->assertArrayHasKey('type', $nodeQueryFields['args']['id']);
        $this->assertInstanceOf(NonNull::class, $nodeQueryFields['args']['id']['type']);
        /** @var NonNull $idType */
        $idType = $nodeQueryFields['args']['id']['type'];
        $this->assertSame(GraphQLType::id(), $idType->getWrappedType());
        $this->assertSame($itemResolver, $nodeQueryFields['resolve']);
    }

    /**
     * @dataProvider itemQueryFieldsProvider
     */
    public function testGetItemQueryFields(string $resourceClass, Operation $operation, string $queryName, array $configuration, ?GraphQLType $graphqlType, ?callable $resolver, array $expectedQueryFields): void
    {
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, $queryName, $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$queryName => $operation])]));
        $this->itemResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $queryName)->willReturn($resolver);

        $queryFields = $this->fieldsBuilder->getItemQueryFields($resourceClass, $operation, $queryName, $configuration);

        $this->assertEquals($expectedQueryFields, $queryFields);
    }

    public function itemQueryFieldsProvider(): array
    {
        return [
            'no resource field configuration' => ['resourceClass', (new Query()), 'action', [], null, null, []],
            'nominal standard type case with deprecation reason and description' => ['resourceClass', (new Query())->withShortName('ShortName')->withDeprecationReason('not useful')->withDescription('Custom description.'), 'action', [], GraphQLType::string(), null,
                [
                    'actionShortName' => [
                        'type' => GraphQLType::string(),
                        'description' => 'Custom description.',
                        'args' => [
                            'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
                        ],
                        'resolve' => null,
                        'deprecationReason' => 'not useful',
                    ],
                ],
            ],
            'nominal item case' => ['resourceClass', (new Query())->withShortName('ShortName'), 'action', [], $graphqlType = new ObjectType(['name' => 'item']), $resolver = function () {
            },
                [
                    'actionShortName' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
                            'id' => ['type' => GraphQLType::nonNull(GraphQLType::id())],
                        ],
                        'resolve' => $resolver,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'empty overridden args and add fields' => [
                'resourceClass', (new Query())->withShortName('ShortName'), 'item_query', ['args' => [], 'name' => 'customActionName'], GraphQLType::string(), null,
                [
                    'shortName' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                        'name' => 'customActionName',
                    ],
                ],
            ],
            'override args with custom ones' => [
                'resourceClass', (new Query())->withShortName('ShortName'), 'item_query', ['args' => ['customArg' => ['type' => 'a type']]], GraphQLType::string(), null,
                [
                    'shortName' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [
                            'customArg' => [
                                'type' => GraphQLType::string(),
                            ],
                        ],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider collectionQueryFieldsProvider
     */
    public function testGetCollectionQueryFields(string $resourceClass, Operation $operation, string $queryName, array $configuration, ?GraphQLType $graphqlType, ?callable $resolver, array $expectedQueryFields): void
    {
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, $queryName, $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(true);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $queryName)->willReturn($graphqlType);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$queryName => $operation])]));
        $this->collectionResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $queryName)->willReturn($resolver);
        $this->filterLocatorProphecy->has('my_filter')->willReturn(true);
        $filterProphecy = $this->prophesize(FilterInterface::class);
        $filterProphecy->getDescription($resourceClass)->willReturn([
            'boolField' => ['type' => 'bool', 'required' => true],
            'boolField[]' => ['type' => 'bool', 'required' => true],
            'parent.child[related.nested]' => ['type' => 'bool', 'required' => true],
            'dateField[before]' => ['type' => \DateTimeInterface::class, 'required' => false],
        ]);
        $this->filterLocatorProphecy->get('my_filter')->willReturn($filterProphecy->reveal());
        $this->typesContainerProphecy->has('ShortNameFilter_dateField')->willReturn(false);
        $this->typesContainerProphecy->has('ShortNameFilter_parent__child')->willReturn(false);
        $this->typesContainerProphecy->set('ShortNameFilter_dateField', Argument::type(ListOfType::class));
        $this->typesContainerProphecy->set('ShortNameFilter_parent__child', Argument::type(ListOfType::class));

        $queryFields = $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $operation, $queryName, $configuration);

        $this->assertEquals($expectedQueryFields, $queryFields);
    }

    public function collectionQueryFieldsProvider(): array
    {
        return [
            'no resource field configuration' => ['resourceClass', new Query(), 'action', [], null, null, []],
            'nominal collection case with deprecation reason and description' => ['resourceClass', (new Query())->withCollection(true)->withShortName('ShortName')->withDeprecationReason('not useful')->withDescription('Custom description.'), 'action', [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
            },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => 'Custom description.',
                        'args' => [
                            'first' => [
                                'type' => GraphQLType::int(),
                                'description' => 'Returns the first n elements from the list.',
                            ],
                            'last' => [
                                'type' => GraphQLType::int(),
                                'description' => 'Returns the last n elements from the list.',
                            ],
                            'before' => [
                                'type' => GraphQLType::string(),
                                'description' => 'Returns the elements in the list that come before the specified cursor.',
                            ],
                            'after' => [
                                'type' => GraphQLType::string(),
                                'description' => 'Returns the elements in the list that come after the specified cursor.',
                            ],
                        ],
                        'resolve' => $resolver,
                        'deprecationReason' => 'not useful',
                    ],
                ],
            ],
            'collection with filters' => ['resourceClass', (new Query())->withShortName('ShortName')->withCollection(true)->withFilters(['my_filter']), 'action', [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
            },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
                            'first' => [
                                'type' => GraphQLType::int(),
                                'description' => 'Returns the first n elements from the list.',
                            ],
                            'last' => [
                                'type' => GraphQLType::int(),
                                'description' => 'Returns the last n elements from the list.',
                            ],
                            'before' => [
                                'type' => GraphQLType::string(),
                                'description' => 'Returns the elements in the list that come before the specified cursor.',
                            ],
                            'after' => [
                                'type' => GraphQLType::string(),
                                'description' => 'Returns the elements in the list that come after the specified cursor.',
                            ],
                            'boolField' => $graphqlType,
                            'boolField_list' => GraphQLType::listOf($graphqlType),
                            'parent__child' => GraphQLType::listOf(new InputObjectType(['name' => 'ShortNameFilter_parent__child', 'fields' => ['related__nested' => $graphqlType]])),
                            'dateField' => GraphQLType::listOf(new InputObjectType(['name' => 'ShortNameFilter_dateField', 'fields' => ['before' => $graphqlType]])),
                        ],
                        'resolve' => $resolver,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'collection empty overridden args and add fields' => [
                'resourceClass', (new Query())->withCollection(true)->withShortName('ShortName')->withArgs([]), 'action', ['args' => [], 'name' => 'customActionName'], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
                },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [],
                        'resolve' => $resolver,
                        'deprecationReason' => null,
                        'name' => 'customActionName',
                    ],
                ],
            ],
            'collection override args with custom ones' => [
                'resourceClass', (new Query())->withCollection(true)->withShortName('ShortName'), 'action', ['args' => ['customArg' => ['type' => 'a type']]], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
                },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
                            'customArg' => [
                                'type' => GraphQLType::string(),
                            ],
                        ],
                        'resolve' => $resolver,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'collection with page-based pagination enabled' => ['resourceClass', (new Query())->withCollection(true)->withShortName('ShortName')->withPaginationType('page')->withFilters(['my_filter']), 'action', [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
            },
                [
                    'actionShortNames' => [
                        'type' => $graphqlType,
                        'description' => null,
                        'args' => [
                            'page' => [
                                'type' => GraphQLType::int(),
                                'description' => 'Returns the current page.',
                            ],
                            'boolField' => $graphqlType,
                            'boolField_list' => GraphQLType::listOf($graphqlType),
                            'parent__child' => GraphQLType::listOf(new InputObjectType(['name' => 'ShortNameFilter_parent__child', 'fields' => ['related__nested' => $graphqlType]])),
                            'dateField' => GraphQLType::listOf(new InputObjectType(['name' => 'ShortNameFilter_dateField', 'fields' => ['before' => $graphqlType]])),
                        ],
                        'resolve' => $resolver,
                        'deprecationReason' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider mutationFieldsProvider
     */
    public function testGetMutationFields(string $resourceClass, Operation $operation, string $mutationName, GraphQLType $graphqlType, GraphQLType $inputGraphqlType, ?callable $mutationResolver, array $expectedMutationFields): void
    {
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, $mutationName, $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, $mutationName, $resourceClass, $resourceClass, null, 0)->willReturn($inputGraphqlType);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $mutationName)->willReturn($graphqlType);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$mutationName => $operation])]));
        $this->itemMutationResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $mutationName)->willReturn($mutationResolver);

        $mutationFields = $this->fieldsBuilder->getMutationFields($resourceClass, $operation, $mutationName);

        $this->assertEquals($expectedMutationFields, $mutationFields);
    }

    public function mutationFieldsProvider(): array
    {
        return [
            'nominal case with deprecation reason' => ['resourceClass', (new Mutation())->withShortName('ShortName')->withDeprecationReason('not useful'), 'action', $graphqlType = new ObjectType(['name' => 'mutation']), $inputGraphqlType = new ObjectType(['name' => 'input']), $mutationResolver = function () {
            },
                [
                    'actionShortName' => [
                        'type' => $graphqlType,
                        'description' => 'Actions a ShortName.',
                        'args' => [
                            'input' => [
                                'type' => $inputGraphqlType,
                                'description' => null,
                                'args' => [],
                                'resolve' => null,
                                'deprecationReason' => 'not useful',
                            ],
                        ],
                        'resolve' => $mutationResolver,
                        'deprecationReason' => 'not useful',
                    ],
                ],
            ],
            'custom description' => ['resourceClass', (new Mutation())->withShortName('ShortName')->withDescription('Custom description.'), 'action', $graphqlType = new ObjectType(['name' => 'mutation']), $inputGraphqlType = new ObjectType(['name' => 'input']), $mutationResolver = function () {
            },
                [
                    'actionShortName' => [
                        'type' => $graphqlType,
                        'description' => 'Custom description.',
                        'args' => [
                            'input' => [
                                'type' => $inputGraphqlType,
                                'description' => null,
                                'args' => [],
                                'resolve' => null,
                                'deprecationReason' => null,
                            ],
                        ],
                        'resolve' => $mutationResolver,
                        'deprecationReason' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider subscriptionFieldsProvider
     */
    public function testGetSubscriptionFields(string $resourceClass, Operation $operation, string $subscriptionName, GraphQLType $graphqlType, GraphQLType $inputGraphqlType, ?callable $subscriptionResolver, array $expectedSubscriptionFields): void
    {
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, $subscriptionName, $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, $subscriptionName, $resourceClass, $resourceClass, null, 0)->willReturn($inputGraphqlType);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $subscriptionName)->willReturn($graphqlType);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$subscriptionName => $operation])]));
        $this->itemSubscriptionResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $subscriptionName)->willReturn($subscriptionResolver);

        $subscriptionFields = $this->fieldsBuilder->getSubscriptionFields($resourceClass, $operation, $subscriptionName);

        $this->assertEquals($expectedSubscriptionFields, $subscriptionFields);
    }

    public function subscriptionFieldsProvider(): array
    {
        return [
            'mercure not enabled' => ['resourceClass', (new Subscription())->withShortName('ShortName'), 'action', new ObjectType(['name' => 'subscription']), new ObjectType(['name' => 'input']), null, [],
            ],
            'nominal case with deprecation reason' => ['resourceClass', (new Subscription())->withShortName('ShortName')->withMercure(true)->withDeprecationReason('not useful'), 'action', $graphqlType = new ObjectType(['name' => 'subscription']), $inputGraphqlType = new ObjectType(['name' => 'input']), $subscriptionResolver = function () {
            },
                [
                    'actionShortNameSubscribe' => [
                        'type' => $graphqlType,
                        'description' => 'Subscribes to the action event of a ShortName.',
                        'args' => [
                            'input' => [
                                'type' => $inputGraphqlType,
                                'description' => null,
                                'args' => [],
                                'resolve' => null,
                                'deprecationReason' => 'not useful',
                            ],
                        ],
                        'resolve' => $subscriptionResolver,
                        'deprecationReason' => 'not useful',
                    ],
                ],
            ],
            'custom description' => ['resourceClass', (new Subscription())->withShortName('ShortName')->withMercure(true)->withDescription('Custom description.'), 'action', $graphqlType = new ObjectType(['name' => 'subscription']), $inputGraphqlType = new ObjectType(['name' => 'input']), $subscriptionResolver = function () {
            },
                [
                    'actionShortNameSubscribe' => [
                        'type' => $graphqlType,
                        'description' => 'Custom description.',
                        'args' => [
                            'input' => [
                                'type' => $inputGraphqlType,
                                'description' => null,
                                'args' => [],
                                'resolve' => null,
                                'deprecationReason' => null,
                            ],
                        ],
                        'resolve' => $subscriptionResolver,
                        'deprecationReason' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider resourceObjectTypeFieldsProvider
     */
    public function testGetResourceObjectTypeFields(string $resourceClass, Operation $operation, array $properties, bool $input, ?string $queryName, ?string $mutationName, ?string $subscriptionName, ?array $ioMetadata, array $expectedResourceObjectTypeFields, ?AdvancedNameConverterInterface $advancedNameConverter = null): void
    {
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection(array_keys($properties)));
        foreach ($properties as $propertyName => $propertyMetadata) {
            $this->propertyMetadataFactoryProphecy->create($resourceClass, $propertyName, ['normalization_groups' => null, 'denormalization_groups' => null])->willReturn($propertyMetadata);
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_NULL), Argument::type('bool'), $queryName, '', $resourceClass, $propertyName, 1)->willReturn(null);
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_CALLABLE), Argument::type('bool'), $queryName, '', $resourceClass, $propertyName, 1)->willReturn('NotRegisteredType');
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), $queryName, '', $resourceClass, $propertyName, 1)->willReturn(GraphQLType::string());
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), $mutationName, '', $resourceClass, $propertyName, 1)->willReturn(GraphQLType::string());
            if ('propertyObject' === $propertyName) {
                $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), $mutationName, 'objectClass', $resourceClass, $propertyName, 1)->willReturn(new ObjectType(['name' => 'objectType']));
                $this->resourceMetadataCollectionFactoryProphecy->create('objectClass')->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['item_query' => new Query()])]));
                $this->itemResolverFactoryProphecy->__invoke('objectClass', $resourceClass, $queryName ?? $mutationName ?? $subscriptionName)->willReturn(static function () {
                });
            }
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), $subscriptionName, '', $resourceClass, $propertyName, 1)->willReturn(GraphQLType::string());
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, $mutationName, 'subresourceClass', $propertyName, 1)->willReturn(GraphQLType::string());
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING)), Argument::type('bool'), $queryName ?? $mutationName ?? $subscriptionName, '', $resourceClass, $propertyName, 1)->willReturn(GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull(GraphQLType::string()))));
        }
        $this->typesContainerProphecy->has('NotRegisteredType')->willReturn(false);
        $this->typesContainerProphecy->all()->willReturn([]);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $operation = $queryName ? new Query() : new Mutation();
        if ($subscriptionName) {
            $operation = new Subscription();
        }
        $this->resourceMetadataCollectionFactoryProphecy->create('resourceClass')->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$queryName ?? $mutationName ?? $subscriptionName => $operation])]));
        $this->resourceMetadataCollectionFactoryProphecy->create('subresourceClass')->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['item_query' => new Query()])]));

        $fieldsBuilder = $this->fieldsBuilder;
        if ($advancedNameConverter) {
            $fieldsBuilder = $this->buildFieldsBuilder($advancedNameConverter);
        }
        $resourceObjectTypeFields = $fieldsBuilder->getResourceObjectTypeFields($resourceClass, $operation, $input, $queryName ?? $mutationName ?? $subscriptionName, 0, $ioMetadata);

        $this->assertEquals($expectedResourceObjectTypeFields, $resourceObjectTypeFields);
    }

    public function resourceObjectTypeFieldsProvider(): array
    {
        $advancedNameConverter = $this->prophesize(AdvancedNameConverterInterface::class);
        $advancedNameConverter->normalize('field', 'resourceClass')->willReturn('normalizedField');

        return [
            'query' => ['resourceClass', new Query(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, true, false),
                    'propertyNotReadable' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, false),
                    'nameConverted' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), null, true, false),
                ],
                false, 'item_query', null, null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                    'name_converted' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'query with advanced name converter' => ['resourceClass', new Query(),
                [
                    'field' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING), null, true, false),
                ],
                false, 'item_query', null, null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'normalizedField' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                ],
                $advancedNameConverter->reveal(),
            ],
            'query input' => ['resourceClass', new Query(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, false),
                ],
                true, 'item_query', null, null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'query with simple non-null string array property' => ['resourceClass', new Query(),
                [
                    'property' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING)), null, true, false),
                ],
                false, 'item_query', null, null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'property' => [
                        'type' => GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull(GraphQLType::string()))),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'mutation non input' => ['resourceClass', new Mutation(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                    'propertyReadable' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, true, true),
                    'propertyObject' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL, false, 'objectClass'), null, true, true),
                ],
                false, null, 'mutation', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyReadable' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                    'propertyObject' => [
                        'type' => GraphQLType::nonNull(new ObjectType(['name' => 'objectType'])),
                        'description' => null,
                        'args' => [],
                        'resolve' => static function () {
                        },
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'mutation input' => ['resourceClass', new Mutation(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), 'propertyBool description', false, true))->withAttributes(['deprecation_reason' => 'not useful']),
                    'propertySubresource' => (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true))->withSubresource(new SubresourceMetadata('subresourceClass')),
                    'id' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), null, false, true),
                ],
                true, null, 'mutation', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => 'propertyBool description',
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => 'not useful',
                    ],
                    'propertySubresource' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                    '_id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'delete mutation input' => ['resourceClass', new Mutation(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'delete', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'create mutation input' => ['resourceClass', new Mutation(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'create', null, null,
                [
                    'propertyBool' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'update mutation input' => ['resourceClass', new Mutation(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'update', null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyBool' => [
                        'type' => GraphQLType::string(),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'subscription non input' => ['resourceClass', new Subscription(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                    'propertyReadable' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, true, true),
                ],
                false, null, null, 'subscription', null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'propertyReadable' => [
                        'type' => GraphQLType::nonNull(GraphQLType::string()),
                        'description' => null,
                        'args' => [],
                        'resolve' => null,
                        'deprecationReason' => null,
                    ],
                ],
            ],
            'subscription input' => ['resourceClass', new Subscription(),
                [
                    'property' => new PropertyMetadata(),
                    'propertyBool' => (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), 'propertyBool description', false, true))->withAttributes(['deprecation_reason' => 'not useful']),
                    'propertySubresource' => (new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true))->withSubresource(new SubresourceMetadata('subresourceClass')),
                    'id' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT), null, false, true),
                ],
                true, null, null, 'subscription', null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'clientSubscriptionId' => GraphQLType::string(),
                ],
            ],
            'null io metadata non input' => ['resourceClass', new Query(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                false, null, 'update', null, ['class' => null], [],
            ],
            'null io metadata input' => ['resourceClass', new Query(),
                [
                    'propertyBool' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_BOOL), null, false, true),
                ],
                true, null, 'update', null, ['class' => null],
                [
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'invalid types' => ['resourceClass', new Query(),
                [
                    'propertyInvalidType' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_NULL), null, true, false),
                    'propertyNotRegisteredType' => new PropertyMetadata(new Type(Type::BUILTIN_TYPE_CALLABLE), null, true, false),
                ],
                false, 'item_query', null, null, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider resolveResourceArgsProvider
     */
    public function testResolveResourceArgs(array $args, array $expectedResolvedArgs, ?string $expectedExceptionMessage = null): void
    {
        if (null !== $expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());

        $args = $this->fieldsBuilder->resolveResourceArgs($args, 'operation', 'shortName');

        $this->assertSame($expectedResolvedArgs, $args);
    }

    public function resolveResourceArgsProvider(): array
    {
        return [
            [[], []],
            [['customArg' => []], [], 'The argument "customArg" of the custom operation "operation" in shortName needs a "type" option.'],
            [['customArg' => ['type' => 'a type']], ['customArg' => ['type' => GraphQLType::string()]]],
        ];
    }
}
