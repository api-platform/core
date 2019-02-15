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

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\ConstantScoreFilterInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Filter\TermFilter;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Property\PropertyMetadata;
use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Foo;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class TermFilterTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            ConstantScoreFilterInterface::class,
            new TermFilter(
                $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
                $this->prophesize(PropertyMetadataFactoryInterface::class)->reveal(),
                $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
                $this->prophesize(IdentifierExtractorInterface::class)->reveal(),
                $this->prophesize(IriConverterInterface::class)->reveal(),
                $this->prophesize(PropertyAccessorInterface::class)->reveal(),
                $this->prophesize(NameConverterInterface::class)->reveal()
            )
        );
    }

    public function testApply()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Foo::class)->willReturn(new PropertyNameCollection(['id', 'name', 'bar']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'id')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)))->shouldBeCalled();

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $foo = new Foo();
        $foo->setName('Xavier');
        $foo->setBar('Thévenard');

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('/foos/1', ['fetch_data' => false])->willReturn($foo)->shouldBeCalled();

        $propertyAccessorProphecy = $this->prophesize(PropertyAccessorInterface::class);
        $propertyAccessorProphecy->getValue($foo, 'id')->willReturn(1)->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('id', Foo::class, null, Argument::type('array'))->willReturn('id')->shouldBeCalled();
        $nameConverterProphecy->normalize('name', Foo::class, null, Argument::type('array'))->willReturn('name')->shouldBeCalled();

        $termFilter = new TermFilter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $identifierExtractorProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $propertyAccessorProphecy->reveal(),
            $nameConverterProphecy->reveal()
        );

        self::assertSame(
            ['bool' => ['must' => [['term' => ['id' => 1]], ['terms' => ['name' => ['Caroline', 'Xavier']]]]]],
            $termFilter->apply([], Foo::class, null, ['filters' => ['id' => '/foos/1', 'name' => ['Caroline', 'Xavier']]])
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

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $nameConverterProphecy = $this->prophesize(NameConverterInterface::class);
        $nameConverterProphecy->normalize('foo.bar', Foo::class, null, Argument::type('array'))->willReturn('foo.bar')->shouldBeCalled();
        $nameConverterProphecy->normalize('foo', Foo::class, null, Argument::type('array'))->willReturn('foo')->shouldBeCalled();

        $termFilter = new TermFilter(
            $this->prophesize(PropertyNameCollectionFactoryInterface::class)->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $identifierExtractorProphecy->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            $nameConverterProphecy->reveal(),
            ['foo.bar' => null]
        );

        self::assertSame(
            ['bool' => ['must' => [['nested' => ['path' => 'foo', 'query' => ['term' => ['foo.bar' => 'Krupicka']]]]]]],
            $termFilter->apply([], Foo::class, null, ['filters' => ['foo.bar' => 'Krupicka']])
        );
    }

    public function testApplyWithInvalidFilters()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Foo::class)->willReturn(new PropertyNameCollection(['id', 'bar']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'id')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'bar')->willReturn(new PropertyMetadata())->shouldBeCalled();

        $identifierExtractorProphecy = $this->prophesize(IdentifierExtractorInterface::class);
        $identifierExtractorProphecy->getIdentifierFromResourceClass(Foo::class)->willReturn('id')->shouldBeCalled();

        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $iriConverterProphecy->getItemFromIri('/invalid_iri_foos/1', ['fetch_data' => false])->willThrow(new InvalidArgumentException())->shouldBeCalled();

        $termFilter = new TermFilter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $this->prophesize(ResourceClassResolverInterface::class)->reveal(),
            $identifierExtractorProphecy->reveal(),
            $iriConverterProphecy->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            $this->prophesize(NameConverterInterface::class)->reveal()
        );

        self::assertSame(
            [],
            $termFilter->apply([], Foo::class, null, ['filters' => ['id' => '/invalid_iri_foos/1', 'bar' => 'Chaverot']])
        );
    }

    public function testGetDescription()
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Foo::class)->willReturn(new PropertyNameCollection(['id', 'name', 'bar', 'date', 'weird']))->shouldBeCalled();

        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Foo::class, 'id')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_INT)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'name')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_STRING)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'bar')->willReturn(new PropertyMetadata())->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'date')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_OBJECT, false, \DateTimeImmutable::class)))->shouldBeCalled();
        $propertyMetadataFactoryProphecy->create(Foo::class, 'weird')->willReturn(new PropertyMetadata(new Type(Type::BUILTIN_TYPE_RESOURCE)))->shouldBeCalled();

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(\DateTimeImmutable::class)->willReturn(false)->shouldBeCalled();

        $termFilter = new TermFilter(
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal(),
            $resourceClassResolverProphecy->reveal(),
            $this->prophesize(IdentifierExtractorInterface::class)->reveal(),
            $this->prophesize(IriConverterInterface::class)->reveal(),
            $this->prophesize(PropertyAccessorInterface::class)->reveal(),
            $this->prophesize(NameConverterInterface::class)->reveal()
        );

        self::assertSame(
            [
                'id' => [
                    'property' => 'id',
                    'type' => 'int',
                    'required' => false,
                ],
                'id[]' => [
                    'property' => 'id',
                    'type' => 'int',
                    'required' => false,
                ],
                'name' => [
                    'property' => 'name',
                    'type' => 'string',
                    'required' => false,
                ],
                'name[]' => [
                    'property' => 'name',
                    'type' => 'string',
                    'required' => false,
                ],
                'date' => [
                    'property' => 'date',
                    'type' => \DateTimeInterface::class,
                    'required' => false,
                ],
                'date[]' => [
                    'property' => 'date',
                    'type' => \DateTimeInterface::class,
                    'required' => false,
                ],
                'weird' => [
                    'property' => 'weird',
                    'type' => 'string',
                    'required' => false,
                ],
                'weird[]' => [
                    'property' => 'weird',
                    'type' => 'string',
                    'required' => false,
                ],
            ],
            $termFilter->getDescription(Foo::class)
        );
    }
}
