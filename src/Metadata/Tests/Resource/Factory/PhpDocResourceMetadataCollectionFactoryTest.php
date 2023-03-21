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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\PhpDocResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Tests\Fixtures\ClassWithNoDocBlock;
use ApiPlatform\Metadata\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class PhpDocResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testExistingDescription(): void
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [new ApiResource(description: 'I am foo')]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataCollectionFactory($decorated);
        $this->assertSame($resourceCollection[0], $factory->create('Foo')[0]);
    }

    public function testNoDocBlock(): void
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [new ApiResource()]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create(ClassWithNoDocBlock::class)->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataCollectionFactory($decorated);
        $this->assertSame($resourceCollection[0], $factory->create(ClassWithNoDocBlock::class)[0]);
    }

    public function testExtractDescription(): void
    {
        $resourceCollection = new ResourceMetadataCollection(DummyEntity::class, [new ApiResource()]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create(DummyEntity::class)->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataCollectionFactory($decorated);
        $this->assertSame('My dummy entity.', $factory->create(DummyEntity::class)[0]->getDescription());
    }
}
