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

use ApiPlatform\Tests\ProphecyTrait;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\InputOutputResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Tests\Fixtures\DummyEntity;
use PHPUnit\Framework\TestCase;

class InputOutputResourceMetadataCollectionFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider getAttributes
     *
     * @param mixed $input
     * @param mixed $expected
     */
    public function testInputOutputMetadata($input, $expected)
    {
        $resourceCollection = new ResourceMetadataCollection('Foo', [new ApiResource(input: $input)]);
        $decoratedProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceCollection)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new InputOutputResourceMetadataCollectionFactory($decorated);
        $this->assertSame($expected, $factory->create('Foo')[0]->getInput());
    }

    public function getAttributes(): array
    {
        return [
            // no input class defined
            [[], null],
            // input is a string
            [DummyEntity::class, ['class' => DummyEntity::class, 'name' => 'DummyEntity']],
            // input is false
            [false, ['class' => null]],
            // input is an array
            [['class' => DummyEntity::class, 'type' => 'Foo'], ['class' => DummyEntity::class, 'type' => 'Foo', 'name' => 'DummyEntity']],
        ];
    }
}
