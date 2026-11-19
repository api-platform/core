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

namespace ApiPlatform\Tests\Symfony\Routing;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Routing\IriConverter;
use ApiPlatform\Symfony\Routing\SkolemIriConverter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Routing\RouterInterface;

class IriConverterTest extends TestCase
{
    use ProphecyTrait;

    public function testGetIriFromItemWithOperation(): void
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $operation, Argument::any())->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldNotBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($item, UrlGeneratorInterface::ABS_PATH, $operation));
    }

    public function testGetIriFromItemWithoutOperation(): void
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $operation, Argument::any())->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($item));
    }

    public function testGetIriFromItemWithContextOperation(): void
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $operation, Argument::any())->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($item, UrlGeneratorInterface::ABS_URL, $operation));
    }

    public function testGetIriFromItemWithoutOperationUsesTheLocalOperationCache(): void
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->exactly(2))
            ->method('generate')
            ->with($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)
            ->willReturn('/dummies/1');

        $identifiersExtractor = $this->createStub(IdentifiersExtractorInterface::class);
        $identifiersExtractor->method('getIdentifiersFromItem')->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->once())
            ->method('create')
            ->with(Dummy::class)
            ->willReturn(new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
            ]));

        $iriConverter = $this->getMockedIriConverter($router, $resourceMetadataCollectionFactory, $identifiersExtractor);

        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($item));
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($item));
    }

    public function testLocalOperationCacheDistinguishesItemAndCollectionIris(): void
    {
        $itemOperationName = 'item_operation';
        $collectionOperationName = 'collection_operation';

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->exactly(4))
            ->method('generate')
            ->willReturnCallback(fn (string $routeName): string => match ($routeName) {
                $itemOperationName => '/dummies/1',
                $collectionOperationName => '/dummies',
                default => $this->fail(\sprintf('Unexpected route name "%s".', $routeName)),
            });

        $resourceMetadataCollectionFactory = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->expects($this->exactly(2))
            ->method('create')
            ->with(Dummy::class)
            ->willReturn(new ResourceMetadataCollection(Dummy::class, [
                (new ApiResource())->withOperations(new Operations([
                    $itemOperationName => (new Get())->withName($itemOperationName)->withClass(Dummy::class),
                    $collectionOperationName => (new GetCollection())->withName($collectionOperationName)->withClass(Dummy::class),
                ])),
            ]));

        $iriConverter = $this->getMockedIriConverter($router, $resourceMetadataCollectionFactory);

        // Both forms pass a string resource, so only the item/collection part of the key differs.
        $context = ['uri_variables' => ['id' => 1]];
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, null, $context));
        $this->assertSame('/dummies', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, new GetCollection()));
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, null, $context));
        $this->assertSame('/dummies', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, new GetCollection()));
    }

    public function testLocalOperationCacheKeyAccountsForItemUriTemplate(): void
    {
        $item = new Dummy();
        $item->setId(1);

        $dummyOperation = (new Get())->withName('dummy_operation')->withClass(Dummy::class);
        $relatedOperation = (new Get())->withName('related_operation')->withClass(RelatedDummy::class);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturnCallback(fn (string $routeName): string => match ($routeName) {
            'dummy_operation' => '/dummies/1',
            'related_operation' => '/related_dummies/1',
            default => $this->fail(\sprintf('Unexpected route name "%s".', $routeName)),
        });

        $identifiersExtractor = $this->createStub(IdentifiersExtractorInterface::class);
        $identifiersExtractor->method('getIdentifiersFromItem')->willReturn(['id' => 1]);

        // An item_uri_template makes the converter skip the resource class promotion, so the same
        // object resolves against Dummy instead of the promoted RelatedDummy.
        $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
        $resourceClassResolver->method('isResourceClass')->willReturn(true);
        $resourceClassResolver->method('getResourceClass')->willReturn(RelatedDummy::class);

        $resourceMetadataCollectionFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactory->method('create')->willReturnCallback(
            static fn (string $resourceClass): ResourceMetadataCollection => new ResourceMetadataCollection($resourceClass, [
                (new ApiResource())->withOperations(new Operations(
                    RelatedDummy::class === $resourceClass
                        ? ['related_operation' => $relatedOperation]
                        : ['dummy_operation' => $dummyOperation]
                )),
            ])
        );

        $operationMetadataFactory = $this->createStub(OperationMetadataFactoryInterface::class);
        $operationMetadataFactory->method('create')->willReturn(null);

        $iriConverter = $this->getMockedIriConverter($router, $resourceMetadataCollectionFactory, $identifiersExtractor, $resourceClassResolver, $operationMetadataFactory);

        $this->assertSame('/related_dummies/1', $iriConverter->getIriFromResource($item));
        $this->assertSame('/dummies/1', $iriConverter->getIriFromResource($item, UrlGeneratorInterface::ABS_PATH, null, ['item_uri_template' => '/unresolved']));
    }

    public function testGetIriFromItemWithNoOperations(): void
    {
        $this->expectExceptionMessage(\sprintf('Unable to generate an IRI for the item of type "%s"', Dummy::class));

        $item = new Dummy();
        $item->setId(1);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations()),
        ]));

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, Argument::type(HttpOperation::class), Argument::any())->willThrow(RuntimeException::class);

        $iriConverter = $this->getIriConverter(null, null, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getIriFromResource($item);
    }

    public function testGetIriFromItemWithBadIdentifiers(): void
    {
        $this->expectExceptionMessage(\sprintf('Unable to generate an IRI for the item of type "%s"', Dummy::class));

        $item = new Dummy();
        $item->setId(1);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations()),
        ]));

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, Argument::type(HttpOperation::class), Argument::any())->willThrow(InvalidArgumentException::class);

        $iriConverter = $this->getIriConverter(null, null, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getIriFromResource($item);
    }

    public function testGetCollectionIri(): void
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName)->withClass(Dummy::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, [], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame('/dummies', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation));
    }

    public function testGetGenidIriFromUnnamedOperation(): void
    {
        $operation = new NotExposed();
        $route = '/.well-known/genid/42';

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_genid', Argument::type('array'), UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn($route);

        $skolemIriConverter = new SkolemIriConverter($routerProphecy->reveal());
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, []));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy, null, $skolemIriConverter);
        $this->assertEquals($route, $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation));
    }

    public function testGetIriFromResourceClassWithIdentifiers(): void
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withClass(Dummy::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1/foo');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation->withName($operationName)])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame('/dummies/1/foo', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_URL, $operation, ['uri_variables' => ['id' => 1]]));
    }

    public function testGetIriFromResourceClassWithoutOperation(): void
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName)->withClass(Dummy::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1/foo');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame('/dummies/1/foo', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_URL, $operation, ['uri_variables' => ['id' => 1]]));
    }

    public function testGetIriFromItemWithItemUriTemplate(): void
    {
        $item = new Dummy();
        $item->setId(1);

        $operation = new GetCollection(name: 'operation_name', class: Dummy::class, itemUriTemplate: '/dummies/another/{id}{._format}');
        $anotherOperation = new Get(name: 'another_name', uriTemplate: '/dummies/{relatedId}/another/{id}{._format}', uriVariables: [
            'relatedId' => new Link(fromClass: RelatedDummy::class, toProperty: 'id'),
            'id' => new Link(fromClass: Dummy::class),
        ]);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('another_name', ['id' => 1, 'relatedId' => 6], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/6/another/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $anotherOperation, Argument::any())->shouldBeCalled()->willReturn(['id' => 1, 'relatedId' => 6]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $operationMetadataFactoryProphecy = $this->prophesize(OperationMetadataFactoryInterface::class);
        $operationMetadataFactoryProphecy->create('/dummies/{relatedId}/another/{id}{._format}')->willReturn($anotherOperation);
        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy, null, null, $operationMetadataFactoryProphecy);
        $this->assertSame('/dummies/6/another/1', $iriConverter->getIriFromResource($item, UrlGeneratorInterface::ABS_URL, $operation, ['item_uri_template' => '/dummies/{relatedId}/another/{id}{._format}']));
    }

    public function testGetIriFromResourceClassWithItemUriTemplateAndUriVariables(): void
    {
        $operation = new GetCollection(name: 'operation_name', class: Dummy::class, itemUriTemplate: '/dummies/another/{id}{._format}');
        $anotherOperation = new Get(name: 'another_name', uriTemplate: '/dummies/another/{id}{._format}');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('another_name', ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/another/1');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $operationMetadataFactoryProphecy = $this->prophesize(OperationMetadataFactoryInterface::class);
        $operationMetadataFactoryProphecy->create('/dummies/another/{id}{._format}')->willReturn($anotherOperation);
        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy, null, null, $operationMetadataFactoryProphecy);
        $this->assertSame('/dummies/another/1', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_URL, $operation, ['uri_variables' => ['id' => 1], 'item_uri_template' => '/dummies/another/{id}{._format}']));
    }

    public function testGetItemFromCollectionIri(): void
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName);
        $this->expectExceptionMessage('The iri "/dummies" references a collection not an item.');
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/dummies')->shouldBeCalled()->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_operation_name' => $operationName,
        ]);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getResourceFromIri('/dummies');
    }

    public function testGetItemFromIri(): void
    {
        $item = new Dummy();
        $operationName = 'operation_name';
        $operation = (new Get())->withUriVariables(['id' => new Link()])->withName($operationName);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/dummies/1')->shouldBeCalled()->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_operation_name' => $operationName,
            'id' => 1,
        ]);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $stateProviderProphecy = $this->prophesize(ProviderInterface::class);
        $stateProviderProphecy->provide($operation, ['id' => 1], Argument::type('array'))->willReturn($item);
        $iriConverter = $this->getIriConverter($stateProviderProphecy, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertSame($item, $iriConverter->getResourceFromIri('/dummies/1'));
    }

    public function testGetNoItemFromIri(): void
    {
        $this->expectExceptionMessage('Item not found for "/dummies/1"');
        $operationName = 'operation_name';
        $operation = (new Get())->withUriVariables(['id' => new Link()])->withName($operationName);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/dummies/1')->shouldBeCalled()->willReturn([
            '_api_resource_class' => Dummy::class,
            '_api_operation_name' => $operationName,
            'id' => 1,
        ]);
        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $stateProviderProphecy = $this->prophesize(ProviderInterface::class);
        $stateProviderProphecy->provide($operation, ['id' => 1], Argument::type('array'))->willReturn(null);
        $iriConverter = $this->getIriConverter($stateProviderProphecy, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getResourceFromIri('/dummies/1');
    }

    private function getMockedIriConverter(RouterInterface $router, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, ?IdentifiersExtractorInterface $identifiersExtractor = null, ?ResourceClassResolverInterface $resourceClassResolver = null, ?OperationMetadataFactoryInterface $operationMetadataFactory = null): IriConverter
    {
        if (!$resourceClassResolver) {
            $resourceClassResolver = $this->createStub(ResourceClassResolverInterface::class);
            $resourceClassResolver->method('isResourceClass')->willReturn(true);
            $resourceClassResolver->method('getResourceClass')->willReturnCallback(static fn (object $object): string => $object::class);
        }

        return new IriConverter(
            $this->createStub(ProviderInterface::class),
            $router,
            $identifiersExtractor ?? $this->createStub(IdentifiersExtractorInterface::class),
            $resourceClassResolver,
            $resourceMetadataCollectionFactory,
            operationMetadataFactory: $operationMetadataFactory,
        );
    }

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(static fn ($args) => true);

        $resourceClassResolver->getResourceClass(Argument::cetera())->will(static fn ($args) => $args[0]::class);

        return $resourceClassResolver->reveal();
    }

    private function getIriConverter(?ObjectProphecy $stateProviderProphecy = null, ?ObjectProphecy $routerProphecy = null, ?ObjectProphecy $identifiersExtractorProphecy = null, $resourceMetadataCollectionFactoryProphecy = null, $uriVariablesConverter = null, $decorated = null, ?ObjectProphecy $operationMetadataFactory = null): IriConverter
    {
        if (!$stateProviderProphecy) {
            $stateProviderProphecy = $this->prophesize(ProviderInterface::class);
        }

        if (!$routerProphecy) {
            $routerProphecy = $this->prophesize(RouterInterface::class);
        }

        if (!$identifiersExtractorProphecy) {
            $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        }

        if (!$resourceMetadataCollectionFactoryProphecy) {
            $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        }

        return new IriConverter($stateProviderProphecy->reveal(), $routerProphecy->reveal(), $identifiersExtractorProphecy->reveal(), $this->getResourceClassResolver(), $resourceMetadataCollectionFactoryProphecy->reveal(), $uriVariablesConverter, $decorated, $operationMetadataFactory?->reveal());
    }
}
