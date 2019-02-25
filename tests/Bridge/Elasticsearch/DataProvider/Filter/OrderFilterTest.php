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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\DataProvider\Filter;

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\SortFilterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class OrderFilterTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            SortFilterInterface::class,
            new OrderFilter(
                $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
                $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
                $this->prophesize(NameConverterInterface::class)->reveal()
            )
        );
    }

    public function testApply()
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)))->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('name', Foo::class, null, Argument::type('array'))->willReturn('name')->shouldBeCalled();

        $orderFilter = new OrderFilter(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $nameConverterProphecy->reveal(),
            'order',
            ['name' => 'asc']
        );

        self::assertSame(
            [['name' => ['order' => 'asc']]],
            $orderFilter->apply([], Foo::class, null, ['filters' => ['order' => ['name' => null]]])
        );
    }

    public function testApplyWithNestedProperty()
    {
        $fooType = new Type(Type::BUILTIN_TYPE_ARRAY, false, Foo::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, Foo::class));
        $barType = new Type(Type::BUILTIN_TYPE_STRING);

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'foo')->willReturn(new PropertyMetadata($fooType))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'bar')->willReturn(new PropertyMetadata($barType))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Foo::class)->willReturn(true)->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('foo.bar', Foo::class, null, Argument::type('array'))->willReturn('foo.bar')->shouldBeCalled();
        $nameConverterProphecy->normalize('foo', Foo::class, null, Argument::type('array'))->willReturn('foo')->shouldBeCalled();

        $orderFilter = new OrderFilter(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $nameConverterProphecy->reveal(),
            'order',
            ['foo.bar' => null]
        );

        self::assertSame(
            [['foo.bar' => ['order' => 'asc', 'nested' => ['path' => 'foo']]]],
            $orderFilter->apply([], Foo::class, null, ['filters' => ['order' => ['foo.bar' => 'asc']]])
        );
    }

    public function testApplyWithInvalidOrderFilter()
    {
        $orderFilter = new OrderFilter(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(NameConverterInterface::class)->reveal(),
            'order'
        );

        self::assertSame([], $orderFilter->apply([], Foo::class, null, ['filters' => ['order' => 'error']]));
    }

    public function testApplyWithInvalidTypeAndInvalidDirection()
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'bar')->willReturn(new PropertyMetadata())->shouldBeCalled();

        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Foo::class)->willReturn(new PropertyNameCollection(['name', 'bar']))->shouldBeCalled();

        $orderFilter = new OrderFilter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(NameConverterInterface::class)->reveal(),
            'order'
        );

        self::assertSame(
            [],
            $orderFilter->apply([], Foo::class, null, ['filters' => ['order' => ['name' => 'error', 'bar' => 'asc']]])
        );
    }

    public function testDescription()
    {
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'bar')->willReturn(new PropertyMetadata())->shouldBeCalled();

        $orderFilter = new OrderFilter(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $this->prophesize(NameConverterInterface::class)->reveal(),
            'order',
            ['name' => 'asc', 'bar' => null]
        );

        self::assertSame(['order[name]' => ['property' => 'name', 'type' => 'string', 'required' => false]], $orderFilter->getDescription(Foo::class));
    }
}
