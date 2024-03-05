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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResource;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\RelatedDummy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;

final class LinkFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider provideCreateLinksFromIdentifiersCases
     */
    public function testCreateLinksFromIdentifiers(array $propertyNames, bool $compositeIdentifier, array $expectedLinks, ?bool $idAsIdentifier = null): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Argument::cetera())->willReturn(new PropertyNameCollection($propertyNames));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'id')->willReturn(null === $idAsIdentifier ? new ApiProperty() : new ApiProperty(identifier: $idAsIdentifier));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'name')->willReturn((new ApiProperty())->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'slug')->willReturn(new ApiProperty());
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'composite1')->willReturn((new ApiProperty())->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'composite2')->willReturn((new ApiProperty())->withIdentifier(true));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $linkFactory = new LinkFactory($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());

        self::assertEquals(
            $expectedLinks,
            $linkFactory->createLinksFromIdentifiers((new Get())->withClass(AttributeResource::class))
        );
    }

    public static function provideCreateLinksFromIdentifiersCases(): \Generator
    {
        yield 'no identifiers no id' => [
            ['slug'],
            'compositeIdentifier' => false,
            [],
        ];
        yield 'id detected as identifier' => [
            ['id'],
            'compositeIdentifier' => false,
            [(new Link())->withFromClass(AttributeResource::class)->withParameterName('id')->withIdentifiers(['id'])],
        ];
        yield 'id forced as identifier' => [
            ['id'],
            'compositeIdentifier' => false,
            [(new Link())->withFromClass(AttributeResource::class)->withParameterName('id')->withIdentifiers(['id'])],
            true,
        ];
        yield 'id forced as no identifier' => [
            ['id'],
            'compositeIdentifier' => false,
            [],
            false,
        ];
        yield 'name identifier' => [
            ['id', 'name'],
            'compositeIdentifier' => false,
            [(new Link())->withFromClass(AttributeResource::class)->withParameterName('name')->withIdentifiers(['name'])],
        ];
        yield 'composite identifier' => [
            ['composite1', 'composite2'],
            'compositeIdentifier' => true,
            [(new Link())->withFromClass(AttributeResource::class)->withParameterName('id')->withIdentifiers(['composite1', 'composite2'])->withCompositeIdentifier(true)],
        ];
    }

    /**
     * @dataProvider provideCreateLinksFromAttributesCases
     */
    public function testCreateLinksFromAttributes(array $builtinTypes, array $expectedLinks): void
    {
        $propertyNameCollectionFactory = new PropertyInfoPropertyNameCollectionFactory(new PropertyInfoExtractor([new ReflectionExtractor()]));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'dummy')->willReturn((new ApiProperty())->withBuiltinTypes($builtinTypes));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $linkFactory = new LinkFactory($propertyNameCollectionFactory, $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());

        self::assertEquals(
            $expectedLinks,
            $linkFactory->createLinksFromAttributes((new Get())->withClass(AttributeResource::class))
        );
    }

    public static function provideCreateLinksFromAttributesCases(): \Generator
    {
        yield 'no builtin types' => [
            [],
            [(new Link())->withFromClass(AttributeResource::class)->withFromProperty('dummy')->withToClass(AttributeResource::class)->withParameterName('dummyId')],
        ];
        yield 'with builtin types' => [
            [new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)],
            [(new Link())->withFromClass(AttributeResource::class)->withFromProperty('dummy')->withToClass(Dummy::class)->withParameterName('dummyId')],
        ];
        yield 'with collection builtin types' => [
            [new Type(Type::BUILTIN_TYPE_ARRAY, false, Dummy::class, true, null, [new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)])],
            [(new Link())->withFromClass(AttributeResource::class)->withFromProperty('dummy')->withToClass(RelatedDummy::class)->withParameterName('dummyId')],
        ];
    }

    public function testCompleteLink(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Argument::cetera())->willReturn(new PropertyNameCollection(['slug', 'composite1', 'composite2']));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'slug')->willReturn(new ApiProperty());
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'composite1')->willReturn((new ApiProperty())->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'composite2')->willReturn((new ApiProperty())->withIdentifier(true));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $linkFactory = new LinkFactory($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());

        self::assertEquals(
            (new Link())->withFromClass(AttributeResource::class)->withIdentifiers(['composite1', 'composite2'])->withCompositeIdentifier(true),
            $linkFactory->completeLink((new Link())->withFromClass(AttributeResource::class))
        );
    }

    public function testCreateLinkFromProperty(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);

        $property = 'test';
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(Dummy::class, 'test')->willReturn(new ApiProperty(builtinTypes: [new Type(builtinType: Type::BUILTIN_TYPE_OBJECT, class: RelatedDummy::class)]));

        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(false);

        $linkFactory = new LinkFactory($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());

        self::assertEquals(
            new Link(fromClass: RelatedDummy::class, toProperty: 'test', identifiers: ['id'], parameterName: 'test'),
            $linkFactory->createLinkFromProperty(new Get(class: Dummy::class), 'test')
        );
    }
}
