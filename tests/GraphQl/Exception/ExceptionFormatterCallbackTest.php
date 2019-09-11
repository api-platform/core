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

use ApiPlatform\Core\GraphQl\Exception\ExceptionFormatterCallback;
use ApiPlatform\Core\GraphQl\Exception\ExceptionFormatterFactory;
use ApiPlatform\Core\GraphQl\Exception\ExceptionFormatterInterface;
use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Mahmood Bazdar<mahmood@bazdar.me>
 */
class ExceptionFormatterCallbackTest extends TestCase
{
    /**
     * @dataProvider formatExceptionProvider
     */
    public function testFormatException(bool $supports, array $expect)
    {
        $errorProphecy = $this->prophesize(\Exception::class)->reveal();
        $error = new Error('Something', null, null, null, null, $errorProphecy);

        $exceptionFormatterProphecy = $this->prophesize(ExceptionFormatterInterface::class);
        $exceptionFormatterProphecy->format($error)->willReturn(['error' => 'test']);
        $exceptionFormatterProphecy->supports($error->getPrevious())->willReturn($supports);
        $exceptionFormatterProphecy->getPriority()->willReturn(1);
        $exceptionFormatter = $exceptionFormatterProphecy->reveal();

        $exceptionFormatterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $exceptionFormatterLocatorProphecy->get('formatter')->willReturn($exceptionFormatter)->shouldBeCalled();
        $exceptionFormattersFactory = new ExceptionFormatterFactory($exceptionFormatterLocatorProphecy->reveal(), ['formatter']);

        $exceptionFormatterCallback = new ExceptionFormatterCallback($exceptionFormattersFactory);
        $this->assertEquals($expect, $exceptionFormatterCallback($error));
    }

    public function formatExceptionProvider(): array
    {
        return [
            'format supported exception' => [true, ['error' => 'test']],
            'falling back to default formatter for unsupported exceptions' => [
                false,
                [
                    'message' => 'Internal server error',
                    'extensions' => [
                        'category' => 'internal',
                    ],
                ],
            ],
        ];
    }

    public function testFormatterPriority()
    {
        $errorProphecy = $this->prophesize(\Exception::class)->reveal();
        $error = new Error('Something', null, null, null, null, $errorProphecy);

        $exceptionFormatter1Prophecy = $this->prophesize(ExceptionFormatterInterface::class);
        $exceptionFormatter1Prophecy->format($error)->willReturn(['error' => 1]);
        $exceptionFormatter1Prophecy->supports($error->getPrevious())->willReturn(true);
        $exceptionFormatter1Prophecy->getPriority()->willReturn(1);
        $exceptionFormatter1 = $exceptionFormatter1Prophecy->reveal();

        $exceptionFormatter2Prophecy = $this->prophesize(ExceptionFormatterInterface::class);
        $exceptionFormatter2Prophecy->format($error)->willReturn(['error' => 2]);
        $exceptionFormatter2Prophecy->supports($error->getPrevious())->willReturn(true);
        $exceptionFormatter2Prophecy->getPriority()->willReturn(2);
        $exceptionFormatter2 = $exceptionFormatter2Prophecy->reveal();

        $exceptionFormatterLocatorProphecy = $this->prophesize(ContainerInterface::class);
        $exceptionFormatterLocatorProphecy->get('formatter1')->willReturn($exceptionFormatter1)->shouldBeCalled();
        $exceptionFormatterLocatorProphecy->get('formatter2')->willReturn($exceptionFormatter2)->shouldBeCalled();

        $exceptionFormattersFactory = new ExceptionFormatterFactory($exceptionFormatterLocatorProphecy->reveal(), ['formatter1', 'formatter2']);

        $exceptionFormatterCallback = new ExceptionFormatterCallback($exceptionFormattersFactory);

        $this->assertEquals(['error' => 2], $exceptionFormatterCallback($error));
    }
}
