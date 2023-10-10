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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\Resource\Factory\LinkResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\AttributeResource;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\RelatedDummy;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\PropertyInfo\Type;

class LinkResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreate(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Argument::cetera())->willReturn(new PropertyNameCollection([
            'id',
            'foo',
            'foo2',
            'bar',
        ]));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'id')->willReturn(new ApiProperty());
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'foo')->willReturn((new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_OBJECT, false, Collection::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)),
        ]));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'foo2')->willReturn((new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_OBJECT, false, Collection::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)),
        ]));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'bar')->willReturn((new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_OBJECT, false, Collection::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, RelatedDummy::class)),
        ]));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $resourceClassResolverProphecy->isResourceClass(RelatedDummy::class)->willReturn(true);
        $linkFactory = new LinkFactory($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    graphQlOperations: [
                        'item_query' => new Query(shortName: 'AttributeResource', class: AttributeResource::class),
                    ]
                ),
            ]),
        );

        $linkResourceMetadataCollectionFactory = new LinkResourceMetadataCollectionFactory($linkFactory, $resourceCollectionMetadataFactoryProphecy->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    graphQlOperations: [
                        'item_query' => (new Query(shortName: 'AttributeResource', class: AttributeResource::class))->withLinks([
                            (new Link())->withFromProperty('foo')->withFromClass(AttributeResource::class)->withToClass(Dummy::class)->withIdentifiers(['id']),
                            (new Link())->withFromProperty('foo2')->withFromClass(AttributeResource::class)->withToClass(Dummy::class)->withIdentifiers(['id']),
                            (new Link())->withFromProperty('bar')->withFromClass(AttributeResource::class)->withToClass(RelatedDummy::class)->withIdentifiers(['id']),
                        ]),
                    ]
                ),
            ]),
            $linkResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }

    public function testCreateWithLinkAttribute(): void
    {
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyNameCollectionFactoryProphecy->create(Argument::cetera())->willReturn(new PropertyNameCollection([
            'identifier',
            'dummy',
        ]));
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'identifier')->willReturn((new ApiProperty())->withIdentifier(true));
        $propertyMetadataFactoryProphecy->create(AttributeResource::class, 'dummy')->willReturn((new ApiProperty())->withBuiltinTypes([
            new Type(Type::BUILTIN_TYPE_OBJECT, false, Collection::class, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, Dummy::class)),
        ]));
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $resourceClassResolverProphecy->isResourceClass(Dummy::class)->willReturn(true);
        $linkFactory = new LinkFactory($propertyNameCollectionFactoryProphecy->reveal(), $propertyMetadataFactoryProphecy->reveal(), $resourceClassResolverProphecy->reveal());
        $resourceCollectionMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceCollectionMetadataFactoryProphecy->create(AttributeResource::class)->willReturn(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    graphQlOperations: [
                        'item_query' => new Query(shortName: 'AttributeResource', class: AttributeResource::class),
                    ]
                ),
            ]),
        );

        $linkResourceMetadataCollectionFactory = new LinkResourceMetadataCollectionFactory($linkFactory, $resourceCollectionMetadataFactoryProphecy->reveal());

        $this->assertEquals(
            new ResourceMetadataCollection(AttributeResource::class, [
                new ApiResource(
                    shortName: 'AttributeResource',
                    class: AttributeResource::class,
                    graphQlOperations: [
                        'item_query' => (new Query(shortName: 'AttributeResource', class: AttributeResource::class))->withLinks([
                            (new Link())->withParameterName('dummyId')->withFromProperty('dummy')->withFromClass(AttributeResource::class)->withToClass(Dummy::class)->withIdentifiers(['identifier']),
                        ]),
                    ]
                ),
            ]),
            $linkResourceMetadataCollectionFactory->create(AttributeResource::class)
        );
    }
}
