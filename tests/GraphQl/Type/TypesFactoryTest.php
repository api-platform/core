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

namespace ApiPlatform\Core\Tests\GraphQl\Type;

use ApiPlatform\Core\GraphQl\Type\Definition\TypeInterface;
use ApiPlatform\Core\GraphQl\Type\TypesFactory;
use ApiPlatform\Core\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
class TypesFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testGetTypes()
    {
        $typeProphecy = $this->prophesize(TypeInterface::class);
        $typeProphecy->getName()->willReturn('Foo');
        $type = $typeProphecy->reveal();

        $typeLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $typeLocatorProphecy->get('foo')->willReturn($type)->shouldBeCalled();

        $typesFactory = new TypesFactory($typeLocatorProphecy->reveal(), ['foo']);

        $types = $typesFactory->getTypes();
        $this->assertArrayHasKey('Foo', $types);
        $this->assertInstanceOf(TypeInterface::class, $types['Foo']);
        $this->assertEquals(['Foo' => $type], $types);
    }
}
