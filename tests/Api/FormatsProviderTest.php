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

namespace ApiPlatform\Core\Tests\Api;

use ApiPlatform\Core\Api\FormatsProvider;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
class FormatsProviderTest extends TestCase
{
    public function testNoResourceClass()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create()->shouldNotBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => 'application/ld+json']);

        $this->assertSame(['jsonld' => 'application/ld+json'], $formatProvider->getFormatsFromAttributes([]));
    }

    public function testResourceClassWithoutFormatsAttributes()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata())->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromAttributes(['resource_class' => 'Foo', 'collection_operation_name' => 'get']));
    }

    public function testResourceClassWithFormatsAttributes()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => ['jsonld']]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromAttributes(['resource_class' => 'Foo', 'collection_operation_name' => 'get']));
    }

    public function testResourceClassWithFormatsAttributesOverRiddingMimeTypes()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => ['jsonld' => ['application/foo'], 'bar' => ['application/bar', 'application/baz'], 'buz' => 'application/fuz']]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/foo'], 'bar' => ['application/bar', 'application/baz'], 'buz' => ['application/fuz']], $formatProvider->getFormatsFromAttributes(['resource_class' => 'Foo', 'collection_operation_name' => 'get']));
    }

    public function testBadFormatsShortDeclaration()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('You either need to add the format \'foo\' to your project configuration or declare a mime type for it in your annotation.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => ['foo']]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json']]);

        $formatProvider->getFormatsFromAttributes(['resource_class' => 'Foo', 'collection_operation_name' => 'get']);
    }

    public function testInvalidFormatsShortDeclaration()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes value must be a string when trying to include an already configured format, array given.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => [['badFormat']]]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromAttributes(['resource_class' => 'Foo', 'collection_operation_name' => 'get']));
    }

    public function testInvalidFormatsDeclaration()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes must be an array, string given for resource class \'Foo\'.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => 'badFormat']);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromAttributes(['resource_class' => 'Foo', 'collection_operation_name' => 'get']));
    }

    public function testResourceClassWithoutFormatsAttributesFromOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn(new ResourceMetadata())->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromOperation('Foo', 'get', OperationType::COLLECTION));
    }

    public function testResourceClassWithFormatsAttributesFromOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => ['jsonld']]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromOperation('Foo', 'get', OperationType::COLLECTION));
    }

    public function testResourceClassWithFormatsAttributesOverRiddingMimeTypesFromOperation()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => ['jsonld' => ['application/foo'], 'bar' => ['application/bar', 'application/baz'], 'buz' => 'application/fuz']]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/foo'], 'bar' => ['application/bar', 'application/baz'], 'buz' => ['application/fuz']], $formatProvider->getFormatsFromOperation('Foo', 'get', OperationType::COLLECTION));
    }

    public function testBadFormatsShortDeclarationFromOperation()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('You either need to add the format \'foo\' to your project configuration or declare a mime type for it in your annotation.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => ['foo']]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json']]);

        $formatProvider->getFormatsFromOperation('Foo', 'get', OperationType::COLLECTION);
    }

    public function testInvalidFormatsShortDeclarationFromOperation()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes value must be a string when trying to include an already configured format, array given.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => [['badFormat']]]);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromOperation('Foo', 'get', OperationType::COLLECTION));
    }

    public function testInvalidFormatsDeclarationFromOperation()
    {
        $this->expectException(\ApiPlatform\Core\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('The \'formats\' attributes must be an array, string given for resource class \'Foo\'.');

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadata = new ResourceMetadata(null, null, null, null, null, ['formats' => 'badFormat']);
        $resourceMetadataFactoryProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();

        $formatProvider = new FormatsProvider($resourceMetadataFactoryProphecy->reveal(), ['jsonld' => ['application/ld+json'], 'json' => ['application/json']]);

        $this->assertSame(['jsonld' => ['application/ld+json']], $formatProvider->getFormatsFromOperation('Foo', 'get', OperationType::COLLECTION));
    }
}
