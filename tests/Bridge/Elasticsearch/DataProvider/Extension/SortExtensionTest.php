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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider\Extension;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\SortExtension;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SortExtensionTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            RequestBodySearchCollectionExtensionInterface::class,
            new SortExtension(
                $this->prophesize(ResourceMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(IdentifierExtractorInterface::class)->reveal(),
                $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
                $this->prophesize(NameConverterInterface::class)->reveal(),
                'asc'
            )
        );
    }

    public function testApplyToCollection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['name', 'bar' => 'desc']]))->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name', Foo::class)->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('bar', Foo::class)->willReturn('bar')->shouldBeCalled();

        $sortExtension = new SortExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(IdentifierExtractorInterface::class)->reveal(), $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), $nameConverterProphecy->reveal(), 'asc');

        self::assertSame(['sort' => [['name' => ['order' => 'asc']], ['bar' => ['order' => 'desc']]]], $sortExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNestedProperty()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata(null, null, null, null, null, ['order' => ['foo.bar' => 'desc']]))->shouldBeCalled();

        $fooType = new Type(Type::BUILTIN_TYPE_ARRAY, false, Foo::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, Foo::class));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willReturn(new PropertyMetadata($fooType))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Foo::class)->willReturn(true)->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('foo.bar', Foo::class)->willReturn('foo.bar')->shouldBeCalled();
        $nameConverterProphecy->normalize('foo', Foo::class)->willReturn('foo')->shouldBeCalled();

        $sortExtension = new SortExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(IdentifierExtractorInterface::class)->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $nameConverterProphecy->reveal(), 'asc');

        self::assertSame(['sort' => [['foo.bar' => ['order' => 'desc', 'nested' => ['path' => 'foo']]]]], $sortExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithDefaultDirection()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('id', Foo::class)->willReturn('id')->shouldBeCalled();

        $sortExtension = new SortExtension($resourceMetadataFactoryProphecy->reveal(), $identifierExtractorProphecy->reveal(), $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), $nameConverterProphecy->reveal(), 'asc');

        self::assertSame(['sort' => [['id' => ['order' => 'asc']]]], $sortExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNoOrdering()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Foo::class)->willReturn(new ResourceMetadata())->shouldBeCalled();

        $sortExtension = new SortExtension($resourceMetadataFactoryProphecy->reveal(), $this->prophesize(IdentifierExtractorInterface::class)->reveal(), $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), $this->prophesize(NameConverterInterface::class)->reveal());

        self::assertEmpty($sortExtension->applyToCollection([], Foo::class));
    }
}
