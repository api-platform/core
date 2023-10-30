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
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Routing\IriConverter;
use ApiPlatform\Symfony\Routing\SkolemIriConverter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\RouterInterface;

class IriConverterTest extends TestCase
{
    use ProphecyTrait;

    public function testGetIriFromItemWithOperation()
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $operation)->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldNotBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1', $iriConverter->getIriFromResource($item, UrlGeneratorInterface::ABS_PATH, $operation));
    }

    public function testGetIriFromItemWithoutOperation()
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $operation)->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1', $iriConverter->getIriFromResource($item));
    }

    public function testGetIriFromItemWithContextOperation()
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, $operation)->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1', $iriConverter->getIriFromResource($item, UrlGeneratorInterface::ABS_URL, $operation));
    }

    public function testGetIriFromItemWithNoOperations()
    {
        $this->expectExceptionMessage(sprintf('Unable to generate an IRI for the item of type "%s"', Dummy::class));

        $item = new Dummy();
        $item->setId(1);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations()),
        ]));

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, Argument::type(HttpOperation::class))->willThrow(RuntimeException::class);

        $iriConverter = $this->getIriConverter(null, null, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getIriFromResource($item);
    }

    public function testGetIriFromItemWithBadIdentifiers()
    {
        $this->expectExceptionMessage(sprintf('Unable to generate an IRI for the item of type "%s"', Dummy::class));

        $item = new Dummy();
        $item->setId(1);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations()),
        ]));

        $identifiersExtractorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractorProphecy->getIdentifiersFromItem($item, Argument::type(HttpOperation::class))->willThrow(InvalidArgumentException::class);

        $iriConverter = $this->getIriConverter(null, null, $identifiersExtractorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getIriFromResource($item);
    }

    public function testGetCollectionIri()
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName)->withClass(Dummy::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, [], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_PATH, $operation));
    }

    public function testGetGenidIriFromUnnamedOperation()
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

    public function testGetIriFromResourceClassWithIdentifiers()
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
        $this->assertEquals('/dummies/1/foo', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_URL, $operation, ['uri_variables' => ['id' => 1]]));
    }

    public function testGetIriFromResourceClassWithoutOperation()
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName)->withClass(Dummy::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1/foo');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1/foo', $iriConverter->getIriFromResource(Dummy::class, UrlGeneratorInterface::ABS_URL, $operation, ['uri_variables' => ['id' => 1]]));
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

    public function testGetItemFromIri()
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
        $this->assertEquals($item, $iriConverter->getResourceFromIri('/dummies/1'));
    }

    public function testGetNoItemFromIri()
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
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(function ($args) {
            return true;
        });

        $resourceClassResolver->getResourceClass(Argument::cetera())->will(function ($args) {
            return \get_class($args[0]);
        });

        return $resourceClassResolver->reveal();
    }

    private function getIriConverter($stateProviderProphecy = null, $routerProphecy = null, $identifiersExtractorProphecy = null, $resourceMetadataCollectionFactoryProphecy = null, $uriVariablesConverter = null, $decorated = null)
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

        return new IriConverter($stateProviderProphecy->reveal(), $routerProphecy->reveal(), $identifiersExtractorProphecy->reveal(), $this->getResourceClassResolver(), $resourceMetadataCollectionFactoryProphecy->reveal(), $uriVariablesConverter, $decorated);
    }
}
