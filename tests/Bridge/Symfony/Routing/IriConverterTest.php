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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Bridge\Symfony\Routing\IriConverter;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameResolverInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Identifier\Normalizer\ChainIdentifierDenormalizer;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
class IriConverterTest extends TestCase
{
    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No route matches "/users/3".
     */
    public function testGetItemFromIriNoRouteException()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willThrow(new RouteNotFoundException())->shouldBeCalledTimes(1);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage No resource associated to "/users/3".
     */
    public function testGetItemFromIriNoResourceException()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([])->shouldBeCalledTimes(1);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Item not found for "/users/3".
     */
    public function testGetItemFromIriItemNotFoundException()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', ['id' => 3], null, [ChainIdentifierDenormalizer::HAS_IDENTIFIER_DENORMALIZER => true])->shouldBeCalledTimes(1);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('3', 'AppBundle\Entity\User')->shouldBeCalled()->willReturn(['id' => 3]);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getItemFromIri('/users/3');
    }

    /**
     * @group legacy
     * @expectedDeprecation Not injecting "ApiPlatform\Core\Identifier\Normalizer\ChainIdentifierDenormalizer" is deprecated since API Platform 2.2 and will not be possible anymore in API Platform 3.
     */
    public function testGetItemFromIri()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', 3, null, ['fetch_data' => true])
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver())
        );
        $converter->getItemFromIri('/users/3', ['fetch_data' => true]);
    }

    public function testGetItemFromIriWithIdentifierNormalizer()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', ['id' => 3], null, ['fetch_data' => true, ChainIdentifierDenormalizer::HAS_IDENTIFIER_DENORMALIZER => true])
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('3', 'AppBundle\Entity\User')->shouldBeCalled()->willReturn(['id' => 3]);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getItemFromIri('/users/3', ['fetch_data' => true]);
    }

    public function testGetItemFromIriWithIdentifierNormalizerAndOperationName()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);
        $itemDataProviderProphecy->getItem('AppBundle\Entity\User', ['id' => 3], 'operation_name', ['fetch_data' => true, ChainIdentifierDenormalizer::HAS_IDENTIFIER_DENORMALIZER => true])
            ->willReturn('foo')
            ->shouldBeCalledTimes(1);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->match('/users/3')->willReturn([
            '_api_resource_class' => 'AppBundle\Entity\User',
            '_api_item_operation_name' => 'operation_name',
            'id' => 3,
        ])->shouldBeCalledTimes(1);

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);
        $identifierDenormalizer->denormalize('3', 'AppBundle\Entity\User')->shouldBeCalled()->willReturn(['id' => 3]);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getItemFromIri('/users/3', ['fetch_data' => true]);
    }

    public function testGetIriFromResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', [], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies');

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $this->assertEquals($converter->getIriFromResourceClass(Dummy::class), '/dummies');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unable to generate an IRI for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy"
     */
    public function testNotAbleToGenerateGetIriFromResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::COLLECTION)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', [], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getIriFromResourceClass(Dummy::class);
    }

    public function testGetSubresourceIriFromResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::SUBRESOURCE, Argument::type('array'))->willReturn('api_dummies_related_dummies_get_subresource');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_related_dummies_get_subresource', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1/related_dummies');

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $this->assertEquals($converter->getSubresourceIriFromResourceClass(Dummy::class, ['subresource_identifiers' => ['id' => 1], 'subresource_resources' => [RelatedDummy::class => 1]]), '/dummies/1/related_dummies');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unable to generate an IRI for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy"
     */
    public function testNotAbleToGenerateGetSubresourceIriFromResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::SUBRESOURCE, Argument::type('array'))->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getSubresourceIriFromResourceClass(Dummy::class, ['subresource_identifiers' => ['id' => 1], 'subresource_resources' => [RelatedDummy::class => 1]]);
    }

    public function testGetItemIriFromResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('api_dummies_get_item');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('api_dummies_get_item', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willReturn('/dummies/1');

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $this->assertEquals($converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]), '/dummies/1');
    }

    /**
     * @expectedException \ApiPlatform\Core\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unable to generate an IRI for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy"
     */
    public function testNotAbleToGenerateGetItemIriFromResourceClass()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $itemDataProviderProphecy = $this->prophesize(ItemDataProviderInterface::class);

        $routeNameResolverProphecy = $this->prophesize(RouteNameResolverInterface::class);
        $routeNameResolverProphecy->getRouteName(Dummy::class, OperationType::ITEM)->willReturn('dummies');

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummies', ['id' => 1], UrlGeneratorInterface::ABS_PATH)->willThrow(new RouteNotFoundException());

        $propertyNameCollectionFactory = $propertyNameCollectionFactoryProphecy->reveal();
        $propertyMetadataFactory = $propertyMetadataFactoryProphecy->reveal();
        $identifierDenormalizer = $this->prophesize(ChainIdentifierDenormalizer::class);

        $converter = new IriConverter(
            $propertyNameCollectionFactory,
            $propertyMetadataFactory,
            $itemDataProviderProphecy->reveal(),
            $routeNameResolverProphecy->reveal(),
            $routerProphecy->reveal(),
            null,
            new IdentifiersExtractor($propertyNameCollectionFactory, $propertyMetadataFactory, null, $this->getResourceClassResolver()),
            $identifierDenormalizer->reveal()
        );
        $converter->getItemIriFromResourceClass(Dummy::class, ['id' => 1]);
    }

    private function getResourceClassResolver()
    {
        $resourceClassResolver = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolver->isResourceClass(Argument::type('string'))->will(function ($args) {
            return true;
        });

        return $resourceClassResolver->reveal();
    }
}
