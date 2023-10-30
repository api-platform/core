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

use ApiPlatform\Api\FilterInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\Type\FieldsBuilder;
use ApiPlatform\GraphQl\Type\TypeBuilderInterface;
use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\Pagination;
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

    /** @var ObjectProphecy */
    private $resourceClassResolverProphecy;

    /** @var FieldsBuilder */
    private $fieldsBuilder;

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
        $this->resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $this->fieldsBuilder = $this->buildFieldsBuilder();
    }

    private function buildFieldsBuilder(AdvancedNameConverterInterface $advancedNameConverter = null): FieldsBuilder
    {
        return new FieldsBuilder($this->propertyNameCollectionFactoryProphecy->reveal(), $this->propertyMetadataFactoryProphecy->reveal(), $this->resourceMetadataCollectionFactoryProphecy->reveal(), $this->resourceClassResolverProphecy->reveal(), $this->typesContainerProphecy->reveal(), $this->typeBuilderProphecy->reveal(), $this->typeConverterProphecy->reveal(), $this->itemResolverFactoryProphecy->reveal(), $this->collectionResolverFactoryProphecy->reveal(), $this->itemMutationResolverFactoryProphecy->reveal(), $this->itemSubscriptionResolverFactoryProphecy->reveal(), $this->filterLocatorProphecy->reveal(), new Pagination(), $advancedNameConverter ?? new CustomConverter(), '__');
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
    public function testGetItemQueryFields(string $resourceClass, Operation $operation, array $configuration, ?GraphQLType $graphqlType, ?callable $resolver, array $expectedQueryFields): void
    {
        $this->resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, Argument::that(static function (Operation $arg) use ($operation): bool {
            return $arg->getName() === $operation->getName();
        }), $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operation->getName() => $operation])]));
        $this->itemResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $operation)->willReturn($resolver);

        $queryFields = $this->fieldsBuilder->getItemQueryFields($resourceClass, $operation, $configuration);

        $this->assertEquals($expectedQueryFields, $queryFields);
    }

    public function itemQueryFieldsProvider(): array
    {
        return [
            'no resource field configuration' => ['resourceClass', (new Query())->withName('action'), [], null, null, []],
            'nominal standard type case with deprecation reason and description' => ['resourceClass', (new Query())->withName('action')->withShortName('ShortName')->withDeprecationReason('not useful')->withDescription('Custom description.'), [], GraphQLType::string(), null,
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
            'nominal item case' => ['resourceClass', (new Query())->withName('action')->withShortName('ShortName'), [], $graphqlType = new ObjectType(['name' => 'item']), $resolver = function () {
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
                'resourceClass', (new Query())->withShortName('ShortName'), ['args' => [], 'name' => 'customActionName'], GraphQLType::string(), null,
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
                'resourceClass', (new Query())->withShortName('ShortName'), ['args' => ['customArg' => ['type' => 'a type']]], GraphQLType::string(), null,
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
    public function testGetCollectionQueryFields(string $resourceClass, Operation $operation, array $configuration, ?GraphQLType $graphqlType, ?callable $resolver, array $expectedQueryFields): void
    {
        $this->resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, Argument::that(static function (Operation $arg) use ($operation): bool {
            return $arg->getName() === $operation->getName();
        }), $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(true);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $operation)->willReturn($graphqlType);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operation->getName() => $operation])]));
        $this->collectionResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $operation)->willReturn($resolver);
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

        $queryFields = $this->fieldsBuilder->getCollectionQueryFields($resourceClass, $operation, $configuration);

        $this->assertEquals($expectedQueryFields, $queryFields);
    }

    public function collectionQueryFieldsProvider(): array
    {
        return [
            'no resource field configuration' => ['resourceClass', (new QueryCollection())->withName('action'), [], null, null, []],
            'nominal collection case with deprecation reason and description' => ['resourceClass', (new QueryCollection())->withName('action')->withShortName('ShortName')->withDeprecationReason('not useful')->withDescription('Custom description.'), [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
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
            'collection with filters' => ['resourceClass', (new QueryCollection())->withName('action')->withShortName('ShortName')->withFilters(['my_filter']), [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
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
                'resourceClass', (new QueryCollection())->withArgs([])->withName('action')->withShortName('ShortName'), ['args' => [], 'name' => 'customActionName'], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
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
                'resourceClass', (new QueryCollection())->withName('action')->withShortName('ShortName'), ['args' => ['customArg' => ['type' => 'a type']]], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
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
            'collection with page-based pagination enabled' => ['resourceClass', (new QueryCollection())->withName('action')->withShortName('ShortName')->withPaginationType('page')->withFilters(['my_filter']), [], $graphqlType = GraphQLType::listOf(new ObjectType(['name' => 'collection'])), $resolver = function () {
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
    public function testGetMutationFields(string $resourceClass, Operation $operation, GraphQLType $graphqlType, GraphQLType $inputGraphqlType, ?callable $mutationResolver, array $expectedMutationFields): void
    {
        $this->resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, Argument::that(static function (Operation $arg) use ($operation): bool {
            return $arg->getName() === $operation->getName();
        }), $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, Argument::that(static function (Operation $arg) use ($operation): bool {
            return $arg->getName() === $operation->getName();
        }), $resourceClass, $resourceClass, null, 0)->willReturn($inputGraphqlType);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $operation->getName())->willReturn($graphqlType);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operation->getName() => $operation])]));
        $this->itemMutationResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $operation)->willReturn($mutationResolver);

        $mutationFields = $this->fieldsBuilder->getMutationFields($resourceClass, $operation);

        $this->assertEquals($expectedMutationFields, $mutationFields);
    }

    public function mutationFieldsProvider(): array
    {
        return [
            'nominal case with deprecation reason' => ['resourceClass', (new Mutation())->withName('action')->withShortName('ShortName')->withDeprecationReason('not useful'), $graphqlType = new ObjectType(['name' => 'mutation']), $inputGraphqlType = new ObjectType(['name' => 'input']), $mutationResolver = function () {
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
            'custom description' => ['resourceClass', (new Mutation())->withName('action')->withShortName('ShortName')->withDescription('Custom description.'), $graphqlType = new ObjectType(['name' => 'mutation']), $inputGraphqlType = new ObjectType(['name' => 'input']), $mutationResolver = function () {
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
    public function testGetSubscriptionFields(string $resourceClass, Operation $operation, GraphQLType $graphqlType, GraphQLType $inputGraphqlType, ?callable $subscriptionResolver, array $expectedSubscriptionFields): void
    {
        $this->resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), false, Argument::that(static function (Operation $arg) use ($operation): bool {
            return $arg->getName() === $operation->getName();
        }), $resourceClass, $resourceClass, null, 0)->willReturn($graphqlType);
        $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, Argument::that(static function (Operation $arg) use ($operation): bool {
            return $arg->getName() === $operation->getName();
        }), $resourceClass, $resourceClass, null, 0)->willReturn($inputGraphqlType);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->typeBuilderProphecy->getResourcePaginatedCollectionType($graphqlType, $resourceClass, $operation->getName())->willReturn($graphqlType);
        $this->resourceMetadataCollectionFactoryProphecy->create($resourceClass)->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operation->getName() => $operation])]));
        $this->itemSubscriptionResolverFactoryProphecy->__invoke($resourceClass, $resourceClass, $operation)->willReturn($subscriptionResolver);

        $subscriptionFields = $this->fieldsBuilder->getSubscriptionFields($resourceClass, $operation);

        $this->assertEquals($expectedSubscriptionFields, $subscriptionFields);
    }

    public function subscriptionFieldsProvider(): array
    {
        return [
            'mercure not enabled' => ['resourceClass', (new Subscription())->withName('action')->withShortName('ShortName'), new ObjectType(['name' => 'subscription']), new ObjectType(['name' => 'input']), null, [],
            ],
            'nominal case with deprecation reason' => ['resourceClass', (new Subscription())->withName('action')->withShortName('ShortName')->withMercure(true)->withDeprecationReason('not useful'), $graphqlType = new ObjectType(['name' => 'subscription']), $inputGraphqlType = new ObjectType(['name' => 'input']), $subscriptionResolver = function () {
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
            'custom description' => ['resourceClass', (new Subscription())->withName('action')->withShortName('ShortName')->withMercure(true)->withDescription('Custom description.'), $graphqlType = new ObjectType(['name' => 'subscription']), $inputGraphqlType = new ObjectType(['name' => 'input']), $subscriptionResolver = function () {
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
    public function testGetResourceObjectTypeFields(string $resourceClass, Operation $operation, array $properties, bool $input, int $depth, ?array $ioMetadata, array $expectedResourceObjectTypeFields, AdvancedNameConverterInterface $advancedNameConverter = null): void
    {
        $this->resourceClassResolverProphecy->isResourceClass($resourceClass)->willReturn(true);
        $this->resourceClassResolverProphecy->isResourceClass(Argument::type('string'))->willReturn(false);
        $this->propertyNameCollectionFactoryProphecy->create($resourceClass)->willReturn(new PropertyNameCollection(array_keys($properties)));
        foreach ($properties as $propertyName => $propertyMetadata) {
            $this->propertyMetadataFactoryProphecy->create($resourceClass, $propertyName, ['normalization_groups' => null, 'denormalization_groups' => null])->willReturn($propertyMetadata);
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_NULL), Argument::type('bool'), Argument::that(static function (Operation $arg) use ($operation): bool {
                return $arg->getName() === $operation->getName();
            }), '', $resourceClass, $propertyName, $depth + 1)->willReturn(null);
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_CALLABLE), Argument::type('bool'), Argument::that(static function (Operation $arg) use ($operation): bool {
                return $arg->getName() === $operation->getName();
            }), '', $resourceClass, $propertyName, $depth + 1)->willReturn('NotRegisteredType');
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), Argument::that(static function (Operation $arg) use ($operation): bool {
                return $arg->getName() === $operation->getName();
            }), '', $resourceClass, $propertyName, $depth + 1)->willReturn(GraphQLType::string());
            if ('propertyObject' === $propertyName) {
                $this->typeConverterProphecy->convertType(Argument::type(Type::class), Argument::type('bool'), Argument::that(static function (Operation $arg) use ($operation): bool {
                    return $arg->getName() === $operation->getName();
                }), 'objectClass', $resourceClass, $propertyName, $depth + 1)->willReturn(new ObjectType(['name' => 'objectType']));
                $this->resourceMetadataCollectionFactoryProphecy->create('objectClass')->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['item_query' => new Query()])]));
                $this->itemResolverFactoryProphecy->__invoke('objectClass', $resourceClass, $operation)->willReturn(static function () {
                });
            }
            $this->typeConverterProphecy->convertType(Argument::type(Type::class), true, Argument::that(static function (Operation $arg) use ($operation): bool {
                return $arg->getName() === $operation->getName();
            }), 'subresourceClass', $propertyName, $depth + 1)->willReturn(GraphQLType::string());
            $this->typeConverterProphecy->convertType(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING)), Argument::type('bool'), Argument::that(static function (Operation $arg) use ($operation): bool {
                return $arg->getName() === $operation->getName();
            }), '', $resourceClass, $propertyName, $depth + 1)->willReturn(GraphQLType::nonNull(GraphQLType::listOf(GraphQLType::nonNull(GraphQLType::string()))));
        }
        $this->typesContainerProphecy->has('NotRegisteredType')->willReturn(false);
        $this->typesContainerProphecy->all()->willReturn([]);
        $this->typeBuilderProphecy->isCollection(Argument::type(Type::class))->willReturn(false);
        $this->resourceMetadataCollectionFactoryProphecy->create('resourceClass')->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations([$operation->getName() => $operation])]));
        $this->resourceMetadataCollectionFactoryProphecy->create('subresourceClass')->willReturn(new ResourceMetadataCollection($resourceClass, [(new ApiResource())->withGraphQlOperations(['item_query' => new Query()])]));

        $fieldsBuilder = $this->fieldsBuilder;
        if ($advancedNameConverter) {
            $fieldsBuilder = $this->buildFieldsBuilder($advancedNameConverter);
        }
        $resourceObjectTypeFields = $fieldsBuilder->getResourceObjectTypeFields($resourceClass, $operation, $input, $depth, $ioMetadata);

        $this->assertEquals($expectedResourceObjectTypeFields, $resourceObjectTypeFields);
    }

    public function resourceObjectTypeFieldsProvider(): array
    {
        $advancedNameConverter = $this->prophesize(AdvancedNameConverterInterface::class);
        $advancedNameConverter->normalize('field', 'resourceClass')->willReturn('normalizedField');

        return [
            'query' => ['resourceClass', new Query(),
                [
                    'property' => new ApiProperty(),
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(true)->withWritable(false),
                    'propertyNotReadable' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(false),
                    'nameConverted' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withReadable(true)->withWritable(false),
                ],
                false, 0, null,
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
                    'field' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_STRING)])->withReadable(true)->withWritable(false),
                ],
                false, 0, null,
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
                    'property' => new ApiProperty(),
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(false),
                ],
                true, 0, null,
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
                    'property' => (new ApiProperty())->withBuiltinTypes([
                        new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING)),
                    ])->withReadable(true)->withWritable(false),
                ],
                false, 0, null,
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
            'mutation non input' => ['resourceClass', (new Mutation())->withName('mutation'),
                [
                    'property' => new ApiProperty(),
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                    'propertyReadable' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(true)->withWritable(true),
                    'propertyObject' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL, false, 'objectClass')])->withReadable(true)->withWritable(true),
                ],
                false, 0, null,
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
            'mutation input' => ['resourceClass', (new Mutation())->withName('mutation'),
                [
                    'property' => new ApiProperty(),
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withDescription('propertyBool description')->withReadable(false)->withWritable(true)->withDeprecationReason('not useful'),
                    'propertySubresource' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                    'id' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withReadable(false)->withWritable(true),
                ],
                true, 0, null,
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
            'mutation nested input' => ['resourceClass', (new Mutation())->withName('mutation'),
                [
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                ],
                true, 1, null,
                [
                    'id' => [
                        'type' => GraphQLType::id(),
                    ],
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
            'delete mutation input' => ['resourceClass', (new Mutation())->withName('delete'),
                [
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                ],
                true, 0, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'create mutation input' => ['resourceClass', (new Mutation())->withName('create'),
                [
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                ],
                true, 0, null,
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
            'update mutation input' => ['resourceClass', (new Mutation())->withName('update'),
                [
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                ],
                true, 0, null,
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
                    'property' => new ApiProperty(),
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                    'propertyReadable' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(true)->withWritable(true),
                ],
                false, 0, null,
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
                    'property' => new ApiProperty(),
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withDescription('propertyBool description')->withReadable(false)->withWritable(true)->withDeprecationReason('not useful'),
                    'propertySubresource' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                    'id' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_INT)])->withReadable(false)->withWritable(true),
                ],
                true, 0, null,
                [
                    'id' => [
                        'type' => GraphQLType::nonNull(GraphQLType::id()),
                    ],
                    'clientSubscriptionId' => GraphQLType::string(),
                ],
            ],
            'null io metadata non input' => ['resourceClass', new Query(),
                [
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                ],
                false, 0, ['class' => null], [],
            ],
            'null io metadata input' => ['resourceClass', new Query(),
                [
                    'propertyBool' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_BOOL)])->withReadable(false)->withWritable(true),
                ],
                true, 0, ['class' => null],
                [
                    'clientMutationId' => GraphQLType::string(),
                ],
            ],
            'invalid types' => ['resourceClass', new Query(),
                [
                    'propertyInvalidType' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_NULL)])->withReadable(true)->withWritable(false),
                    'propertyNotRegisteredType' => (new ApiProperty())->withBuiltinTypes([new Type(Type::BUILTIN_TYPE_CALLABLE)])->withReadable(true)->withWritable(false),
                ],
                false, 0, null,
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
    public function testResolveResourceArgs(array $args, array $expectedResolvedArgs, string $expectedExceptionMessage = null): void
    {
        if (null !== $expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->typeConverterProphecy->resolveType(Argument::type('string'))->willReturn(GraphQLType::string());

        /** @var Operation */
        $operation = (new Query())->withName('operation')->withShortName('shortName');
        $args = $this->fieldsBuilder->resolveResourceArgs($args, $operation);

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
