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
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Symfony\Routing\IriConverter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\RouterInterface;

class IriConverterTest extends TestCase
{
    use ProphecyTrait;

    public function testGetIriFromItemWithOperationName()
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractyorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractyorProphecy->getIdentifiersFromItem($item, $operationName, ['operation' => $operation])->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractyorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1', $iriConverter->getIriFromItem($item, 'operation_name'));
    }

    public function testGetIriFromItemWithoutOperationName()
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractyorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractyorProphecy->getIdentifiersFromItem($item, $operationName, ['operation' => $operation])->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractyorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1', $iriConverter->getIriFromItem($item));
    }

    public function testGetIriFromItemWithContextOperation()
    {
        $item = new Dummy();
        $item->setId(1);

        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1');

        $identifiersExtractyorProphecy = $this->prophesize(IdentifiersExtractorInterface::class);
        $identifiersExtractyorProphecy->getIdentifiersFromItem($item, $operationName, ['operation' => $operation])->shouldBeCalled()->willReturn(['id' => 1]);

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();

        $iriConverter = $this->getIriConverter(null, $routerProphecy, $identifiersExtractyorProphecy, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1', $iriConverter->getIriFromItem($item, null, UrlGeneratorInterface::ABS_URL, ['operation' => $operation]));
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

        $iriConverter = $this->getIriConverter(null, null, null, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getIriFromItem($item);
    }

    public function testGetIriFromResourceClass()
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, [], UrlGeneratorInterface::ABS_PATH)->shouldBeCalled()->willReturn('/dummies');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies', $iriConverter->getIriFromResourceClass(Dummy::class, 'operation_name'));
    }

    public function testGetIriFromResourceClassWithIdentifiers()
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, ['id' => 1], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies/1/foo');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldBeCalled()->willReturn(new ResourceMetadataCollection(Dummy::class, [
            (new ApiResource())->withOperations(new Operations([$operationName => $operation])),
        ]));

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies/1/foo', $iriConverter->getIriFromResourceClass(Dummy::class, 'operation_name', UrlGeneratorInterface::ABS_URL, ['identifiers_values' => ['id' => 1]]));
    }

    public function testGetIriFromResourceClassWithContextOperation()
    {
        $operationName = 'operation_name';
        $operation = (new GetCollection())->withName($operationName);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate($operationName, [], UrlGeneratorInterface::ABS_URL)->shouldBeCalled()->willReturn('/dummies');

        $resourceMetadataCollectionFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataCollectionFactoryProphecy->create(Dummy::class)->shouldNotBeCalled();

        $iriConverter = $this->getIriConverter(null, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals('/dummies', $iriConverter->getIriFromResourceClass(Dummy::class, 'operation_name', UrlGeneratorInterface::ABS_URL, ['operation' => $operation]));
    }

    public function testGetItemFromCollectionIri()
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
        $iriConverter->getItemFromIri('/dummies');
    }

    public function testGetItemFromIri()
    {
        $item = new Dummy();
        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName)->withUriVariables(['id' => []]);
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
        $stateProviderProphecy->provide(Dummy::class, ['id' => 1], $operationName, Argument::type('array'))->willReturn($item);
        $iriConverter = $this->getIriConverter($stateProviderProphecy, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $this->assertEquals($item, $iriConverter->getItemFromIri('/dummies/1'));
    }

    public function testGetNoItemFromIri()
    {
        $this->expectExceptionMessage('Item not found for "/dummies/1"');
        $operationName = 'operation_name';
        $operation = (new Get())->withName($operationName)->withUriVariables(['id' => []]);
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
        $stateProviderProphecy->provide(Dummy::class, ['id' => 1], $operationName, Argument::type('array'))->willReturn(null);
        $iriConverter = $this->getIriConverter($stateProviderProphecy, $routerProphecy, null, $resourceMetadataCollectionFactoryProphecy);
        $iriConverter->getItemFromIri('/dummies/1');
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

    private function getIriConverter($stateProviderProphecy = null, $routerProphecy = null, $identifiersExtractorProphecy = null, $resourceMetadataCollectionFactoryProphecy = null, $uriVariablesConverter = null)
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

        $iriConverter = $this->prophesize(IriConverterInterface::class);

        return new IriConverter($stateProviderProphecy->reveal(), $routerProphecy->reveal(), $identifiersExtractorProphecy->reveal(), $this->getResourceClassResolver(), $resourceMetadataCollectionFactoryProphecy->reveal(), $uriVariablesConverter, $iriConverter->reveal());
    }
}
