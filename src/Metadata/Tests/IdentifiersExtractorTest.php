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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\IdentifiersExtractor;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\Dummy;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\DummyWithEnumIdentifier;
use ApiPlatform\Metadata\Tests\Fixtures\ApiResource\RelationMultiple;
use ApiPlatform\Metadata\Tests\Fixtures\State\RelationMultipleProvider;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @author Tomasz Grochowski <tg@urias.it>
 */
class IdentifiersExtractorTest extends TestCase
{
    use ProphecyTrait;

    public function testGetIdentifiersFromItem(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = $this->prophesize(HttpOperation::class);
        $item = new Dummy();
        $resourceClass = Dummy::class;
        $operation->getClass()->willReturn(null);

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass($item)->willReturn($resourceClass);
        $operation->getUriVariables()->willReturn([]);

        $this->assertEquals([], $identifiersExtractor->getIdentifiersFromItem($item, $operation->reveal()));
    }

    public function testGetIdentifiersFromItemWithOperation(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = $this->prophesize(HttpOperation::class);
        $item = new Dummy();
        $resourceClass = Dummy::class;
        $operation->getClass()->willReturn($resourceClass);
        $operation->getUriVariables()->willReturn([]);

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolverProphecy->getResourceClass($item)->shouldNotBeCalled();

        $this->assertEquals([], $identifiersExtractor->getIdentifiersFromItem($item, $operation->reveal()));
    }

    public function testGetIdentifiersFromItemWithId(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = (new Get())->withUriVariables(['id' => (new Link())->withIdentifiers(['id'])->withFromClass(Dummy::class)->withParameterName('id')]);
        $item = new Dummy();
        $item->setId(1);
        $resourceClass = Dummy::class;

        $resourceClassResolverProphecy->getResourceClass($item)->willReturn($resourceClass);

        $this->assertEquals(['id' => 1], $identifiersExtractor->getIdentifiersFromItem($item, $operation));
    }

    public function testGetIdentifiersFromItemWithToProperty(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = (new Get())
            ->withUriTemplate('/resources/{firstId}/relations/{secondId}')
            ->withUriVariables([
                'firstId' => (new Link())
                    ->withFromClass(Dummy::class)
                    ->withToProperty('first')
                    ->withIdentifiers(['id'])
                    ->withParameterName('firstId'),
                'secondId' => (new Link())
                    ->withFromClass(Dummy::class)
                    ->withToProperty('second')
                    ->withIdentifiers(['id'])
                    ->withParameterName('secondId'),
            ])
            ->withProvider(RelationMultipleProvider::class)
            ->withClass(RelationMultiple::class);

        $first = new Dummy();
        $first->setId(1);
        $second = new Dummy();
        $second->setId(2);

        $item = new RelationMultiple();
        $item->id = 1;
        $item->first = $first;
        $item->second = $second;

        $resourceClass = RelationMultiple::class;

        $resourceClassResolverProphecy->getResourceClass($item)->willReturn($resourceClass);

        $this->assertEquals(
            [
                'firstId' => 1,
                'secondId' => 2,
            ],
            $identifiersExtractor->getIdentifiersFromItem($item, $operation)
        );
    }

    public function testGetIdentifierWithEnumValues(): void
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceClassResolverProphecy = $this->prophesize(ResourceClassResolverInterface::class);
        $propertyNameCollectionFactoryProphecy = $this->prophesize(PropertyNameCollectionFactoryInterface::class);
        $propertyMetadataFactoryProphecy = $this->prophesize(PropertyMetadataFactoryInterface::class);

        $resourceClassResolverProphecy->isResourceClass(Argument::any())->willReturn(true);
        $resourceClassResolver = $resourceClassResolverProphecy->reveal();

        $identifiersExtractor = new IdentifiersExtractor(
            $resourceMetadataFactoryProphecy->reveal(),
            $resourceClassResolver,
            $propertyNameCollectionFactoryProphecy->reveal(),
            $propertyMetadataFactoryProphecy->reveal()
        );

        $operation = (new Get())
            ->withUriTemplate('/resources/{stringEnumAsIdentifier}/{intEnumAsIdentifier}')
            ->withUriVariables([
                'stringEnumAsIdentifier' => (new Link())
                    ->withFromClass(DummyWithEnumIdentifier::class)
                    ->withParameterName('stringEnumAsIdentifier')
                    ->withIdentifiers(['stringEnumAsIdentifier']),
                'intEnumAsIdentifier' => (new Link())
                    ->withFromClass(DummyWithEnumIdentifier::class)
                    ->withParameterName('intEnumAsIdentifier')
                    ->withIdentifiers(['intEnumAsIdentifier']),
            ])
            ->withClass(DummyWithEnumIdentifier::class);

        $this->assertSame(
            [
                'stringEnumAsIdentifier' => 'foo',
                'intEnumAsIdentifier' => '1',
            ],
            $identifiersExtractor->getIdentifiersFromItem(new DummyWithEnumIdentifier(), $operation)
        );
    }
}
