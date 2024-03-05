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

use ApiPlatform\Api\IdentifiersExtractorInterface;
use ApiPlatform\Api\ResourceClassResolverInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
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

    public function testGetIriFromItemWithNoOperations(): void
    {
        $this->expectExceptionMessage(sprintf('Unable to generate an IRI for the item of type "%s"', Dummy::class));

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
        $this->expectExceptionMessage(sprintf('Unable to generate an IRI for the item of type "%s"', Dummy::class));

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

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(fn ($args) => true);

        $resourceClassResolver->getResourceClass(Argument::cetera())->will(fn ($args) => $args[0]::class);

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
