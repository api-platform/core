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

namespace ApiPlatform\Core\Tests\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\Factory\InputOutputResourceMetadataFactory;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;

class InputOutputResourceMetadataFactoryTest extends TestCase
{
    /**
     * @dataProvider getAttributes
     */
    public function testInputOutputMetadata($attributes, $expected)
    {
        $resourceMetadata = (new ResourceMetadata(null))->withAttributes($attributes);
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceMetadataFactory($decorated);
        $this->assertSame($expected, $factory->create('Foo')->getAttributes()['input']);
    }

    /**
     * @dataProvider getAttributes
     */
    public function testInputOutputViaGraphQlMetadata($attributes, $expected)
    {
        $resourceMetadata = (new ResourceMetadata(null))->withGraphQl(['create' => $attributes]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceMetadataFactory($decorated);
        $this->assertSame($expected, $factory->create('Foo')->getGraphqlAttribute('create', 'input'));
    }

    public function getAttributes(): array
    {
        return [
            // no input class defined
            [[], null],
            // input is a string
            [['input' => DummyEntity::class], ['class' => DummyEntity::class, 'name' => 'DummyEntity']],
            // input is false
            [['input' => false], ['class' => null]],
            // input is an array
            [['input' => ['class' => DummyEntity::class, 'type' => 'Foo']], ['class' => DummyEntity::class, 'type' => 'Foo', 'name' => 'DummyEntity']],
        ];
    }
}
