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

namespace ApiPlatform\Core\Tests\GraphQl\Exception;

use ApiPlatform\Core\GraphQl\Exception\ExceptionFormatterFactory;
use ApiPlatform\Core\GraphQl\Exception\ExceptionFormatterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Mahmood Bazdar<mahmood@bazdar.me>
 */
class ExceptionFormatterFactoryTest extends TestCase
{
    public function testGetExceptionFormatters()
    {
        $exceptionFormatterProphecy = $this->prophesize(ExceptionFormatterInterface::class);
        $exceptionFormatterProphecy->supports()->willReturn(true);
        $exceptionFormatterProphecy->getPriority()->willReturn(1);
        $exceptionFormatter = $exceptionFormatterProphecy->reveal();

        $exceptionFormatterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $exceptionFormatterLocatorProphecy->get('foo')->willReturn($exceptionFormatter)->shouldBeCalled();

        $exceptionFormattersFactory = new ExceptionFormatterFactory($exceptionFormatterLocatorProphecy->reveal(), ['foo']);

        $formatters = $exceptionFormattersFactory->getExceptionFormatters();
        $this->assertArrayHasKey('foo', $formatters);
        $this->assertInstanceOf(ExceptionFormatterInterface::class, $formatters['foo']);
        $this->assertEquals(['foo' => $exceptionFormatter], $formatters);
    }
}
