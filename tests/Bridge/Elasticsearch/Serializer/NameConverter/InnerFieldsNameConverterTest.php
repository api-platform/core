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

namespace ApiPlatform\Core\Tests\Bridge\Elasticsearch\Serializer\NameConverter;

use ApiPlatform\Core\Bridge\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class InnerFieldsNameConverterTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            NameConverterInterface::class,
            new InnerFieldsNameConverter($this->prophesize(NameConverterInterface::class)->reveal())
        );
    }

    public function testNormalize()
    {
        $decoratedProphecy = $this->prophesize(NameConverterInterface::class);
        $decoratedProphecy->normalize('fooBar')->willReturn('foo_bar')->shouldBeCalled();
        $decoratedProphecy->normalize('bazQux')->willReturn('baz_qux')->shouldBeCalled();

        $innerFieldsNameConverter = new InnerFieldsNameConverter($decoratedProphecy->reveal());

        self::assertSame('foo_bar.baz_qux', $innerFieldsNameConverter->normalize('fooBar.bazQux'));
    }

    public function testDenormalize()
    {
        $decoratedProphecy = $this->prophesize(NameConverterInterface::class);
        $decoratedProphecy->denormalize('foo_bar')->willReturn('fooBar')->shouldBeCalled();
        $decoratedProphecy->denormalize('baz_qux')->willReturn('bazQux')->shouldBeCalled();

        $innerFieldsNameConverter = new InnerFieldsNameConverter($decoratedProphecy->reveal());

        self::assertSame('fooBar.bazQux', $innerFieldsNameConverter->denormalize('foo_bar.baz_qux'));
    }
}
