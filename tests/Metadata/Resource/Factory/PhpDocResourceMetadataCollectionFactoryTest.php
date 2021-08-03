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

namespace ApiPlatform\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\PhpDocResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\ClassWithNoDocBlock;
use ApiPlatform\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;

class PhpDocResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testExistingDescription()
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [new ApiResource(description: 'I am foo')]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataCollectionFactory($decorated);
        $this->assertSame($resourceCollection[0], $factory->create('Foo')[0]);
    }

    public function testNoDocBlock()
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [new ApiResource()]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create(ClassWithNoDocBlock::class)->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataCollectionFactory($decorated);
        $this->assertSame($resourceCollection[0], $factory->create(ClassWithNoDocBlock::class)[0]);
    }

    public function testExtractDescription()
    {
        $resourceCollection = new ResourceMetadataCollection(DummyEntity::class, [new ApiResource()]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create(DummyEntity::class)->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new PhpDocResourceMetadataCollectionFactory($decorated);
        $this->assertSame('My dummy entity.', $factory->create(DummyEntity::class)[0]->getDescription());
    }
}
