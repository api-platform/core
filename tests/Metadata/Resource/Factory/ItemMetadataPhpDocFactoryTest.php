<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ItemMetadataPhpDocFactory;
use ApiPlatform\Core\Metadata\Resource\ItemMetadata;
use ApiPlatform\Core\Tests\Fixtures\ClassWithNoDocBlock;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;

class ItemMetadataPhpDocFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testExistingDescription()
    {
        $itemMetadata = new ItemMetadata(null, 'My desc');
        $decoratedProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($itemMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new ItemMetadataPhpDocFactory($decorated);
        $this->assertSame($itemMetadata, $factory->create('Foo'));
    }

    public function testNoDocBlock()
    {
        $itemMetadata = new ItemMetadata();
        $decoratedProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $decoratedProphecy->create(ClassWithNoDocBlock::class)->willReturn($itemMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new ItemMetadataPhpDocFactory($decorated);
        $this->assertSame($itemMetadata, $factory->create(ClassWithNoDocBlock::class));
    }

    public function testExtractDescription()
    {
        $this->markTestSkipped('Require Prophecy to update to phpDocumentor/reflection-docblock 3.');

        $decoratedProphecy = $this->prophesize(ItemMetadataFactoryInterface::class);
        $decoratedProphecy->create(DummyEntity::class)->willReturn(new ItemMetadata())->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new ItemMetadataPhpDocFactory($decorated);
        $this->assertSame('My dummy entity.', $factory->create(DummyEntity::class)->getDescription());
    }
}
