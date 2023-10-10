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

use ApiPlatform\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\GraphQl\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\GraphQl\Tests\Fixtures\Enum\GamePlayMode;
use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\TypeBuilder;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\Pagination\Pagination;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypeBuilderTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $typesContainerProphecy;
    /** @var callable */
    private $defaultFieldResolver;
    private ObjectProphecy $fieldsBuilderLocatorProphecy;
    private TypeBuilder $typeBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->defaultFieldResolver = static function (): void {
        };
        $this->fieldsBuilderLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->typeBuilder = new TypeBuilder(
            $this->typesContainerProphecy->reveal(),
            $this->defaultFieldResolver,
            $this->fieldsBuilderLocatorProphecy->reveal(),
            new Pagination()
        );
    }

    public function testGetResourceObjectType(): void
    {
        $resourceMetadataCollection = new ResourceMetadataCollection('resourceClass', [
            (new ApiResource())->withGraphQlOperations(['collection_query' => new QueryCollection()]),
        ]);
        $this->typesContainerProphecy->has('shortName')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('shortName', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Query())->withShortName('shortName')->withDescription('description');
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadataCollection, $operation, false);
        $this->assertSame('shortName', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, false, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    public function testGetResourceObjectTypeOutputClass(): void
    {
        $resourceMetadataCollection = new ResourceMetadataCollection('resourceClass', [
            (new ApiResource())->withGraphQlOperations(['collection_query' => new QueryCollection()]),
        ]);
        $this->typesContainerProphecy->has('shortName')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('shortName', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Query())->withShortName('shortName')->withDescription('description')->withOutput(['class' => 'outputClass']);
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadataCollection, $operation, false);
        $this->assertSame('shortName', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('outputClass', $operation, false, 0, ['class' => 'outputClass'])->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    /**
     * @dataProvider resourceObjectTypeQuerySerializationGroupsProvider
     */
    public function testGetResourceObjectTypeQuerySerializationGroups(string $itemSerializationGroup, string $collectionSerializationGroup, Operation $operation, string $shortName): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', [(new ApiResource())->withGraphQlOperations([
            'item_query' => (new Query())->withShortName('shortName')->withNormalizationContext(['groups' => [$itemSerializationGroup]]),
            'collection_query' => (new QueryCollection())->withShortName('shortName')->withNormalizationContext(['groups' => [$collectionSerializationGroup]]),
        ])]);
        $this->typesContainerProphecy->has($shortName)->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set($shortName, Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false);
        $this->assertSame($shortName, $resourceObjectType->name);
    }

    public static function resourceObjectTypeQuerySerializationGroupsProvider(): array
    {
        return [
            'same serialization groups for item_query and collection_query' => [
                'group',
                'group',
                (new Query())->withShortName('shortName')->withNormalizationContext(['groups' => ['group']]),
                'shortName',
            ],
            'different serialization groups for item_query and collection_query when using item_query' => [
                'item_group',
                'collection_group',
                (new Query())->withShortName('shortName')->withNormalizationContext(['groups' => ['item_group']]),
                'shortNameItem',
            ],
            'different serialization groups for item_query and collection_query when using collection_query' => [
                'item_group',
                'collection_group',
                (new QueryCollection())->withName('collection_query')->withShortName('shortName')->withNormalizationContext(['groups' => ['collection_group']]),
                'shortNameCollection',
            ],
        ];
    }

    public function testGetResourceObjectTypeInput(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', []);
        $this->typesContainerProphecy->has('customShortNameInput')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('customShortNameInput', Argument::type(NonNull::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Mutation())->withName('custom')->withShortName('shortName')->withDescription('description');
        /** @var NonNull $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, true);
        /** @var InputObjectType $wrappedType */
        $wrappedType = $resourceObjectType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $wrappedType);
        $this->assertSame('customShortNameInput', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, true, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeNestedInput(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', []);
        $this->typesContainerProphecy->has('customShortNameNestedInput')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('customShortNameNestedInput', Argument::type(NonNull::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Mutation())->withName('custom')->withShortName('shortName')->withDescription('description');
        /** @var NonNull $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, true, false, 1);
        /** @var InputObjectType $wrappedType */
        $wrappedType = $resourceObjectType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $wrappedType);
        $this->assertSame('customShortNameNestedInput', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, true, 1, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeCustomMutationInputArgs(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', []);
        $this->typesContainerProphecy->has('customShortNameInput')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('customShortNameInput', Argument::type(NonNull::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Mutation())->withArgs([])->withName('custom')->withShortName('shortName')->withDescription('description');
        /** @var NonNull $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, true);
        /** @var InputObjectType $wrappedType */
        $wrappedType = $resourceObjectType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $wrappedType);
        $this->assertSame('customShortNameInput', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, true, 0, null)
            ->shouldBeCalled()->willReturn(['clientMutationId' => GraphQLType::string()]);
        $fieldsBuilderProphecy->resolveResourceArgs([], $operation)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeMutation(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', [(new ApiResource())->withGraphQlOperations([
            'create' => (new Mutation())->withName('create')->withShortName('shortName')->withDescription('description'),
            'item_query' => (new Query())->withShortName('shortName')->withDescription('description'),
            'collection_query' => new QueryCollection(),
        ])]);
        $this->typesContainerProphecy->has('createShortNamePayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNamePayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Mutation())->withName('create')->withShortName('shortName')->withDescription('description');
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false);
        $this->assertSame('createShortNamePayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertEquals([], $resourceObjectType->config['interfaces']);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        // Recursive call (not using wrapped type)
        $this->typesContainerProphecy->has('shortName')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('shortName', Argument::type(ObjectType::class))->shouldBeCalled();

        $fieldsType = $resourceObjectType->config['fields']();
        $this->assertArrayHasKey('shortName', $fieldsType);
        $this->assertArrayHasKey('clientMutationId', $fieldsType);
        $this->assertSame(GraphQLType::string(), $fieldsType['clientMutationId']);
    }

    public function testGetResourceObjectTypeMutationWrappedType(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', [(new ApiResource())->withGraphQlOperations([
            'item_query' => (new Query())->withShortName('shortName')->withDescription('description')->withNormalizationContext(['groups' => ['item_query']]),
            'create' => (new Mutation())->withName('create')->withShortName('shortName')->withDescription('description')->withNormalizationContext(['groups' => ['create']]),
        ])]);
        $this->typesContainerProphecy->has('createShortNamePayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNamePayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Mutation())->withName('create')->withShortName('shortName')->withDescription('description')->withNormalizationContext(['groups' => ['create']]);
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false);
        $this->assertSame('createShortNamePayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertEquals([], $resourceObjectType->config['interfaces']);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        // Recursive call (using wrapped type)
        $this->typesContainerProphecy->has('createShortNamePayloadData')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNamePayloadData', Argument::type(ObjectType::class))->shouldBeCalled();

        $fieldsType = $resourceObjectType->config['fields']();
        $this->assertArrayHasKey('shortName', $fieldsType);
        $this->assertArrayHasKey('clientMutationId', $fieldsType);
        $this->assertSame(GraphQLType::string(), $fieldsType['clientMutationId']);

        /** @var ObjectType $wrappedType */
        $wrappedType = $fieldsType['shortName'];
        $this->assertSame('createShortNamePayloadData', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertSame($this->defaultFieldResolver, $wrappedType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $wrappedType->config);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, false, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeMutationNested(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', []);
        $this->typesContainerProphecy->has('createShortNameNestedPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNameNestedPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Mutation())->withName('create')->withShortName('shortName')->withDescription('description');
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false, false, 1);
        $this->assertSame('createShortNameNestedPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, false, 1, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    public function testGetResourceObjectTypeSubscription(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', [(new ApiResource())->withGraphQlOperations([
            'update' => (new Subscription())->withName('update')->withShortName('shortName')->withDescription('description')->withMercure(true),
            'item_query' => (new Query())->withShortName('shortName')->withDescription('description'),
            'collection_query' => new QueryCollection(),
        ])]);
        $this->typesContainerProphecy->has('updateShortNameSubscriptionPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Subscription())->withName('update')->withShortName('shortName')->withDescription('description')->withMercure(true);
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false);
        $this->assertSame('updateShortNameSubscriptionPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertEquals([], $resourceObjectType->config['interfaces']);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        // Recursive call (not using wrapped type)
        $this->typesContainerProphecy->has('shortName')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('shortName', Argument::type(ObjectType::class))->shouldBeCalled();

        $fieldsType = $resourceObjectType->config['fields']();
        $this->assertArrayHasKey('shortName', $fieldsType);
        $this->assertArrayHasKey('clientSubscriptionId', $fieldsType);
        $this->assertArrayHasKey('mercureUrl', $fieldsType);
        $this->assertSame(GraphQLType::string(), $fieldsType['clientSubscriptionId']);
        $this->assertSame(GraphQLType::string(), $fieldsType['mercureUrl']);
    }

    public function testGetResourceObjectTypeSubscriptionWrappedType(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', [(new ApiResource())->withGraphQlOperations([
            'item_query' => (new Query())->withShortName('shortName')->withDescription('description')->withNormalizationContext(['groups' => ['item_query']]),
            'update' => (new Subscription())->withName('update')->withShortName('shortName')->withDescription('description')->withNormalizationContext(['groups' => ['update']]),
        ])]);
        $this->typesContainerProphecy->has('updateShortNameSubscriptionPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Subscription())->withName('update')->withShortName('shortName')->withDescription('description')->withNormalizationContext(['groups' => ['update']]);
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false);
        $this->assertSame('updateShortNameSubscriptionPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertEquals([], $resourceObjectType->config['interfaces']);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        // Recursive call (using wrapped type)
        $this->typesContainerProphecy->has('updateShortNameSubscriptionPayloadData')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionPayloadData', Argument::type(ObjectType::class))->shouldBeCalled();

        $fieldsType = $resourceObjectType->config['fields']();
        $this->assertArrayHasKey('shortName', $fieldsType);
        $this->assertArrayHasKey('clientSubscriptionId', $fieldsType);
        $this->assertArrayNotHasKey('mercureUrl', $fieldsType);
        $this->assertSame(GraphQLType::string(), $fieldsType['clientSubscriptionId']);

        /** @var ObjectType $wrappedType */
        $wrappedType = $fieldsType['shortName'];
        $this->assertSame('updateShortNameSubscriptionPayloadData', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertSame($this->defaultFieldResolver, $wrappedType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $wrappedType->config);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, false, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeSubscriptionNested(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('resourceClass', [(new ApiResource())->withGraphQlOperations([])]);
        $this->typesContainerProphecy->has('updateShortNameSubscriptionNestedPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionNestedPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var Operation $operation */
        $operation = (new Subscription())->withName('update')->withShortName('shortName')->withDescription('description')->withMercure(true);
        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, $operation, false, false, 1);
        $this->assertSame('updateShortNameSubscriptionNestedPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $operation, false, 1, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    public function testGetNodeInterface(): void
    {
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        $nodeInterface = $this->typeBuilder->getNodeInterface();
        $this->assertSame('Node', $nodeInterface->name);
        $this->assertSame('A node, according to the Relay specification.', $nodeInterface->description);
        $this->assertArrayHasKey('id', $nodeInterface->getFields());

        $this->assertNull($nodeInterface->resolveType([], [], $this->prophesize(ResolveInfo::class)->reveal()));

        $this->typesContainerProphecy->has('Dummy')->shouldBeCalled()->willReturn(false);
        $this->assertNull($nodeInterface->resolveType([ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => Dummy::class], [], $this->prophesize(ResolveInfo::class)->reveal()));

        $this->typesContainerProphecy->has('Dummy')->shouldBeCalled()->willReturn(true);
        $this->typesContainerProphecy->get('Dummy')->shouldBeCalled()->willReturn(GraphQLType::string());
        /** @var GraphQLType $resolvedType */
        $resolvedType = $nodeInterface->resolveType([ItemNormalizer::ITEM_RESOURCE_CLASS_KEY => Dummy::class], [], $this->prophesize(ResolveInfo::class)->reveal());
        $this->assertSame(GraphQLType::string(), $resolvedType);
    }

    public function testCursorBasedGetPaginatedCollectionType(): void
    {
        /** @var Operation $operation */
        $operation = (new Query())->withPaginationType('cursor');
        $this->typesContainerProphecy->has('StringCursorConnection')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('StringCursorConnection', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->set('StringEdge', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->set('StringPageInfo', Argument::type(ObjectType::class))->shouldBeCalled();

        /** @var ObjectType $resourcePaginatedCollectionType */
        $resourcePaginatedCollectionType = $this->typeBuilder->getPaginatedCollectionType(GraphQLType::string(), $operation);
        $this->assertSame('StringCursorConnection', $resourcePaginatedCollectionType->name);
        $this->assertSame('Cursor connection for String.', $resourcePaginatedCollectionType->description);

        $resourcePaginatedCollectionTypeFields = $resourcePaginatedCollectionType->getFields();
        $this->assertArrayHasKey('edges', $resourcePaginatedCollectionTypeFields);
        $this->assertArrayHasKey('pageInfo', $resourcePaginatedCollectionTypeFields);
        $this->assertArrayHasKey('totalCount', $resourcePaginatedCollectionTypeFields);

        /** @var ListOfType $edgesType */
        $edgesType = $resourcePaginatedCollectionTypeFields['edges']->getType();
        /** @var ObjectType $wrappedType */
        $wrappedType = $edgesType->getWrappedType();
        $this->assertSame('StringEdge', $wrappedType->name);
        $this->assertSame('Edge of String.', $wrappedType->description);
        $edgeObjectTypeFields = $wrappedType->getFields();
        $this->assertArrayHasKey('node', $edgeObjectTypeFields);
        $this->assertArrayHasKey('cursor', $edgeObjectTypeFields);
        $this->assertSame(GraphQLType::string(), $edgeObjectTypeFields['node']->getType());
        $this->assertInstanceOf(NonNull::class, $edgeObjectTypeFields['cursor']->getType());
        $this->assertSame(GraphQLType::string(), $edgeObjectTypeFields['cursor']->getType()->getWrappedType());

        /** @var NonNull $pageInfoType */
        $pageInfoType = $resourcePaginatedCollectionTypeFields['pageInfo']->getType();
        /** @var ObjectType $wrappedType */
        $wrappedType = $pageInfoType->getWrappedType();
        $this->assertSame('StringPageInfo', $wrappedType->name);
        $this->assertSame('Information about the current page.', $wrappedType->description);
        $pageInfoObjectTypeFields = $wrappedType->getFields();
        $this->assertArrayHasKey('endCursor', $pageInfoObjectTypeFields);
        $this->assertArrayHasKey('startCursor', $pageInfoObjectTypeFields);
        $this->assertArrayHasKey('hasNextPage', $pageInfoObjectTypeFields);
        $this->assertArrayHasKey('hasPreviousPage', $pageInfoObjectTypeFields);
        $this->assertSame(GraphQLType::string(), $pageInfoObjectTypeFields['endCursor']->getType());
        $this->assertSame(GraphQLType::string(), $pageInfoObjectTypeFields['startCursor']->getType());
        $this->assertInstanceOf(NonNull::class, $pageInfoObjectTypeFields['hasNextPage']->getType());
        $this->assertSame(GraphQLType::boolean(), $pageInfoObjectTypeFields['hasNextPage']->getType()->getWrappedType());
        $this->assertInstanceOf(NonNull::class, $pageInfoObjectTypeFields['hasPreviousPage']->getType());
        $this->assertSame(GraphQLType::boolean(), $pageInfoObjectTypeFields['hasPreviousPage']->getType()->getWrappedType());

        /** @var NonNull $totalCountType */
        $totalCountType = $resourcePaginatedCollectionTypeFields['totalCount']->getType();
        $this->assertInstanceOf(NonNull::class, $totalCountType);
        $this->assertSame(GraphQLType::int(), $totalCountType->getWrappedType());
    }

    public function testPageBasedGetPaginatedCollectionType(): void
    {
        /** @var Operation $operation */
        $operation = (new Query())->withPaginationType('page');
        $this->typesContainerProphecy->has('StringPageConnection')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('StringPageConnection', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->set('StringPaginationInfo', Argument::type(ObjectType::class))->shouldBeCalled();

        /** @var ObjectType $resourcePaginatedCollectionType */
        $resourcePaginatedCollectionType = $this->typeBuilder->getPaginatedCollectionType(GraphQLType::string(), $operation);
        $this->assertSame('StringPageConnection', $resourcePaginatedCollectionType->name);
        $this->assertSame('Page connection for String.', $resourcePaginatedCollectionType->description);

        $resourcePaginatedCollectionTypeFields = $resourcePaginatedCollectionType->getFields();
        $this->assertArrayHasKey('collection', $resourcePaginatedCollectionTypeFields);
        $this->assertArrayHasKey('paginationInfo', $resourcePaginatedCollectionTypeFields);

        /** @var NonNull $paginationInfoType */
        $paginationInfoType = $resourcePaginatedCollectionTypeFields['paginationInfo']->getType();
        /** @var ObjectType $wrappedType */
        $wrappedType = $paginationInfoType->getWrappedType();
        $this->assertSame('StringPaginationInfo', $wrappedType->name);
        $this->assertSame('Information about the pagination.', $wrappedType->description);
        $paginationInfoObjectTypeFields = $wrappedType->getFields();
        $this->assertArrayHasKey('itemsPerPage', $paginationInfoObjectTypeFields);
        $this->assertArrayHasKey('lastPage', $paginationInfoObjectTypeFields);
        $this->assertArrayHasKey('totalCount', $paginationInfoObjectTypeFields);
        $this->assertInstanceOf(NonNull::class, $paginationInfoObjectTypeFields['itemsPerPage']->getType());
        $this->assertSame(GraphQLType::int(), $paginationInfoObjectTypeFields['itemsPerPage']->getType()->getWrappedType());
        $this->assertInstanceOf(NonNull::class, $paginationInfoObjectTypeFields['lastPage']->getType());
        $this->assertSame(GraphQLType::int(), $paginationInfoObjectTypeFields['lastPage']->getType()->getWrappedType());
        $this->assertInstanceOf(NonNull::class, $paginationInfoObjectTypeFields['totalCount']->getType());
        $this->assertSame(GraphQLType::int(), $paginationInfoObjectTypeFields['totalCount']->getType()->getWrappedType());
    }

    public function testGetEnumType(): void
    {
        $enumClass = GamePlayMode::class;
        $enumName = 'GamePlayMode';
        $enumDescription = 'GamePlayModeEnum description';
        /** @var Operation $operation */
        $operation = (new Operation())
            ->withClass($enumClass)
            ->withShortName($enumName)
            ->withDescription('GamePlayModeEnum description');

        $this->typesContainerProphecy->has('GamePlayModeEnum')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('GamePlayModeEnum', Argument::type(EnumType::class))->shouldBeCalled();
        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderEnumInterface::class);
        $enumValues = [
            GamePlayMode::CO_OP->name => ['value' => GamePlayMode::CO_OP->value],
            GamePlayMode::MULTI_PLAYER->name => ['value' => GamePlayMode::MULTI_PLAYER->value],
            GamePlayMode::SINGLE_PLAYER->name => ['value' => GamePlayMode::SINGLE_PLAYER->value, 'description' => 'Which is played by a lone player.'],
        ];
        $fieldsBuilderProphecy->getEnumFields($enumClass)->willReturn($enumValues);
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->willReturn($fieldsBuilderProphecy->reveal());

        self::assertEquals(new EnumType([
            'name' => 'GamePlayModeEnum',
            'description' => $enumDescription,
            'values' => $enumValues,
        ]), $this->typeBuilder->getEnumType($operation));
    }

    /**
     * @dataProvider typesProvider
     */
    public function testIsCollection(Type $type, bool $expectedIsCollection): void
    {
        $this->assertSame($expectedIsCollection, $this->typeBuilder->isCollection($type));
    }

    public static function typesProvider(): array
    {
        return [
            [new Type(Type::BUILTIN_TYPE_BOOL), false],
            [new Type(Type::BUILTIN_TYPE_OBJECT), false],
            [new Type(Type::BUILTIN_TYPE_RESOURCE, false, null, false), false],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true), false],
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true), false],
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT)), false],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, 'className', true), false],
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'className')), true],
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, new Type(Type::BUILTIN_TYPE_OBJECT, false, 'className')), true],
        ];
    }
}
