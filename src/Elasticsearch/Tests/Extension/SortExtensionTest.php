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

namespace ApiPlatform\Elasticsearch\Tests\Extension;

use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Extension\SortExtension;
use ApiPlatform\Elasticsearch\Tests\Fixtures\Foo;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SortExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            RequestBodySearchCollectionExtensionInterface::class,
            new SortExtension(
                $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
                $this->prophesize(NameConverterInterface::class)->reveal(),
                'asc'
            )
        );
    }

    public function testApplyToCollection(): void
    {
        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name', Foo::class)->willReturn('name')->shouldBeCalled();
        $nameConverterProphecy->normalize('bar', Foo::class)->willReturn('bar')->shouldBeCalled();

        $sortExtension = new SortExtension($this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), $nameConverterProphecy->reveal(), 'asc');

        self::assertEquals(['sort' => [['name' => ['order' => 'asc']], ['bar' => ['order' => 'desc']]]], $sortExtension->applyToCollection([], Foo::class, (new GetCollection())->withOrder(['name', 'bar' => 'desc'])));
    }

    public function testApplyToCollectionWithNestedProperty(): void
    {
        $fooType = new Type(Type::BUILTIN_TYPE_ARRAY, false, Foo::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, Foo::class));

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willReturn((new ApiProperty())->withBuiltinTypes([$fooType]))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Foo::class)->willReturn(true)->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('foo.bar', Foo::class)->willReturn('foo.bar')->shouldBeCalled();
        $nameConverterProphecy->normalize('foo', Foo::class)->willReturn('foo')->shouldBeCalled();

        $sortExtension = new SortExtension($propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal(), $nameConverterProphecy->reveal(), 'asc');

        self::assertEquals(['sort' => [['foo.bar' => ['order' => 'desc', 'nested' => ['path' => 'foo']]]]], $sortExtension->applyToCollection([], Foo::class, (new GetCollection())->withOrder(['foo.bar' => 'desc'])));
    }

    public function testApplyToCollectionWithDefaultDirection(): void
    {
        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('id', Foo::class)->willReturn('id')->shouldBeCalled();

        $sortExtension = new SortExtension($this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), $nameConverterProphecy->reveal(), 'asc');

        self::assertEquals(['sort' => [['id' => ['order' => 'asc']]]], $sortExtension->applyToCollection([], Foo::class));
    }

    public function testApplyToCollectionWithNoOrdering(): void
    {
        $sortExtension = new SortExtension($this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(), $this->prophesize(ResourceClassResolverInterface::class)->reveal(), $this->prophesize(NameConverterInterface::class)->reveal());

        self::assertEmpty($sortExtension->applyToCollection([], Foo::class));
    }
}
