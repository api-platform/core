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

use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\GraphQl\Serializer\ItemNormalizer;
use ApiPlatform\Core\GraphQl\Type\FieldsBuilderInterface;
use ApiPlatform\Core\GraphQl\Type\TypeBuilder;
use ApiPlatform\Core\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\ProphecyTrait;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphQLType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypeBuilderTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy */
    private $typesContainerProphecy;

    /** @var callable */
    private $defaultFieldResolver;

    /** @var ObjectProphecy */
    private $fieldsBuilderLocatorProphecy;

    /** @var ObjectProphecy */
    private $resourceMetadataFactoryProphecy;

    /** @var TypeBuilder */
    private $typeBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->typesContainerProphecy = $this->prophesize(TypesContainerInterface::class);
        $this->defaultFieldResolver = function () {
        };
        $this->fieldsBuilderLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $this->resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $this->typeBuilder = new TypeBuilder(
            $this->typesContainerProphecy->reveal(),
            $this->defaultFieldResolver,
            $this->fieldsBuilderLocatorProphecy->reveal(),
            new Pagination($this->resourceMetadataFactoryProphecy->reveal())
        );
    }

    public function testGetResourceObjectType(): void
    {
        $resourceMetadata = new ResourceMetadata('shortName', 'description');
        $this->typesContainerProphecy->has('shortName')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('shortName', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, 'item_query', null, null);
        $this->assertSame('shortName', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, false, 'item_query', null, null, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    public function testGetResourceObjectTypeOutputClass(): void
    {
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))
            ->withGraphql(['item_query' => ['output' => ['class' => 'outputClass']]]);
        $this->typesContainerProphecy->has('shortName')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('shortName', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, 'item_query', null, null);
        $this->assertSame('shortName', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('outputClass', $resourceMetadata, false, 'item_query', null, null, 0, ['class' => 'outputClass'])->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    /**
     * @dataProvider resourceObjectTypeQuerySerializationGroupsProvider
     */
    public function testGetResourceObjectTypeQuerySerializationGroups(string $itemSerializationGroup, string $collectionSerializationGroup, string $shortName, string $queryName)
    {
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))
            ->withGraphql([
                'item_query' => ['normalization_context' => ['groups' => [$itemSerializationGroup]]],
                'collection_query' => ['normalization_context' => ['groups' => [$collectionSerializationGroup]]],
            ]);
        $this->typesContainerProphecy->has($shortName)->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set($shortName, Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, $queryName, null, null);
        $this->assertSame($shortName, $resourceObjectType->name);
    }

    public function resourceObjectTypeQuerySerializationGroupsProvider(): array
    {
        return [
            'same serialization groups for item_query and collection_query' => [
                'group',
                'group',
                'shortName',
                'item_query',
            ],
            'different serialization groups for item_query and collection_query when using item_query' => [
                'item_group',
                'collection_group',
                'shortNameItem',
                'item_query',
            ],
            'different serialization groups for item_query and collection_query when using collection_query' => [
                'item_group',
                'collection_group',
                'shortNameCollection',
                'collection_query',
            ],
        ];
    }

    public function testGetResourceObjectTypeInput(): void
    {
        $resourceMetadata = new ResourceMetadata('shortName', 'description');
        $this->typesContainerProphecy->has('customShortNameInput')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('customShortNameInput', Argument::type(NonNull::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var NonNull $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, true, null, 'custom', null);
        /** @var InputObjectType $wrappedType */
        $wrappedType = $resourceObjectType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $wrappedType);
        $this->assertSame('customShortNameInput', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertArrayHasKey('interfaces', $wrappedType->config);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, true, null, 'custom', null, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeCustomMutationInputArgs(): void
    {
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))
            ->withGraphql(['custom' => ['args' => []]]);
        $this->typesContainerProphecy->has('customShortNameInput')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('customShortNameInput', Argument::type(NonNull::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var NonNull $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, true, null, 'custom', null);
        /** @var InputObjectType $wrappedType */
        $wrappedType = $resourceObjectType->getWrappedType();
        $this->assertInstanceOf(InputObjectType::class, $wrappedType);
        $this->assertSame('customShortNameInput', $wrappedType->name);
        $this->assertSame('description', $wrappedType->description);
        $this->assertArrayHasKey('interfaces', $wrappedType->config);
        $this->assertArrayHasKey('fields', $wrappedType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, true, null, 'custom', null, 0, null)
            ->shouldBeCalled()->willReturn(['clientMutationId' => GraphQLType::string()]);
        $fieldsBuilderProphecy->resolveResourceArgs([], 'custom', 'shortName')->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeMutation(): void
    {
        $resourceMetadata = new ResourceMetadata('shortName', 'description');
        $this->typesContainerProphecy->has('createShortNamePayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNamePayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, null, 'create', null);
        $this->assertSame('createShortNamePayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertSame([], $resourceObjectType->config['interfaces']);
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
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))
            ->withGraphql([
                'item_query' => ['normalization_context' => ['groups' => ['item_query']]],
                'create' => ['normalization_context' => ['groups' => ['create']]],
            ]);
        $this->typesContainerProphecy->has('createShortNamePayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNamePayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, null, 'create', null);
        $this->assertSame('createShortNamePayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertSame([], $resourceObjectType->config['interfaces']);
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

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, false, null, 'create', null, 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeMutationNested(): void
    {
        $resourceMetadata = new ResourceMetadata('shortName', 'description');
        $this->typesContainerProphecy->has('createShortNameNestedPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('createShortNameNestedPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, null, 'create', null, false, 1);
        $this->assertSame('createShortNameNestedPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, false, null, 'create', null, 1, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $resourceObjectType->config['fields']();
    }

    public function testGetResourceObjectTypeSubscription(): void
    {
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))->withAttributes(['mercure' => true]);
        $this->typesContainerProphecy->has('updateShortNameSubscriptionPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, null, null, 'update');
        $this->assertSame('updateShortNameSubscriptionPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertSame([], $resourceObjectType->config['interfaces']);
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
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))
            ->withGraphql([
                'item_query' => ['normalization_context' => ['groups' => ['item_query']]],
                'update' => ['normalization_context' => ['groups' => ['update']]],
            ]);
        $this->typesContainerProphecy->has('updateShortNameSubscriptionPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, null, null, 'update');
        $this->assertSame('updateShortNameSubscriptionPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertSame([], $resourceObjectType->config['interfaces']);
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

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, false, null, null, 'update', 0, null)->shouldBeCalled();
        $this->fieldsBuilderLocatorProphecy->get('api_platform.graphql.fields_builder')->shouldBeCalled()->willReturn($fieldsBuilderProphecy->reveal());
        $wrappedType->config['fields']();
    }

    public function testGetResourceObjectTypeSubscriptionNested(): void
    {
        $resourceMetadata = (new ResourceMetadata('shortName', 'description'))->withAttributes(['mercure' => true]);
        $this->typesContainerProphecy->has('updateShortNameSubscriptionNestedPayload')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('updateShortNameSubscriptionNestedPayload', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->has('Node')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('Node', Argument::type(InterfaceType::class))->shouldBeCalled();

        /** @var ObjectType $resourceObjectType */
        $resourceObjectType = $this->typeBuilder->getResourceObjectType('resourceClass', $resourceMetadata, false, null, null, 'update', false, 1);
        $this->assertSame('updateShortNameSubscriptionNestedPayload', $resourceObjectType->name);
        $this->assertSame('description', $resourceObjectType->description);
        $this->assertSame($this->defaultFieldResolver, $resourceObjectType->resolveFieldFn);
        $this->assertArrayHasKey('interfaces', $resourceObjectType->config);
        $this->assertArrayHasKey('fields', $resourceObjectType->config);

        $fieldsBuilderProphecy = $this->prophesize(FieldsBuilderInterface::class);
        $fieldsBuilderProphecy->getResourceObjectTypeFields('resourceClass', $resourceMetadata, false, null, null, 'update', 1, null)->shouldBeCalled();
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

    public function testCursorBasedGetResourcePaginatedCollectionType(): void
    {
        $this->typesContainerProphecy->has('StringConnection')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('StringConnection', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->set('StringEdge', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->set('StringPageInfo', Argument::type(ObjectType::class))->shouldBeCalled();

        $this->resourceMetadataFactoryProphecy->create('StringResourceClass')->shouldBeCalled()->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['pagination_type' => 'cursor']
        ));

        /** @var ObjectType $resourcePaginatedCollectionType */
        $resourcePaginatedCollectionType = $this->typeBuilder->getResourcePaginatedCollectionType(GraphQLType::string(), 'StringResourceClass', 'operationName');
        $this->assertSame('StringConnection', $resourcePaginatedCollectionType->name);
        $this->assertSame('Connection for String.', $resourcePaginatedCollectionType->description);

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

    public function testPageBasedGetResourcePaginatedCollectionType(): void
    {
        $this->typesContainerProphecy->has('StringConnection')->shouldBeCalled()->willReturn(false);
        $this->typesContainerProphecy->set('StringConnection', Argument::type(ObjectType::class))->shouldBeCalled();
        $this->typesContainerProphecy->set('StringPaginationInfo', Argument::type(ObjectType::class))->shouldBeCalled();

        $this->resourceMetadataFactoryProphecy->create('StringResourceClass')->shouldBeCalled()->willReturn(new ResourceMetadata(
            null,
            null,
            null,
            null,
            null,
            ['pagination_type' => 'page']
        ));

        /** @var ObjectType $resourcePaginatedCollectionType */
        $resourcePaginatedCollectionType = $this->typeBuilder->getResourcePaginatedCollectionType(GraphQLType::string(), 'StringResourceClass', 'operationName');
        $this->assertSame('StringConnection', $resourcePaginatedCollectionType->name);
        $this->assertSame('Connection for String.', $resourcePaginatedCollectionType->description);

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

    /**
     * @dataProvider typesProvider
     */
    public function testIsCollection(Type $type, bool $expectedIsCollection): void
    {
        $this->assertSame($expectedIsCollection, $this->typeBuilder->isCollection($type));
    }

    public function typesProvider(): array
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
