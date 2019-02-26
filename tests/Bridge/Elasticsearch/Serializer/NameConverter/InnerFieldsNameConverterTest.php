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
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class InnerFieldsNameConverterTest extends TestCase
{
    public function testConstruct()
    {
        self::assertInstanceOf(
            AdvancedNameConverterInterface::class,
            new InnerFieldsNameConverter($this->prophesize(AdvancedNameConverterInterface::class)->reveal())
        );
    }

    public function testNormalize()
    {
        $decoratedProphecy = $this->prophesize(AdvancedNameConverterInterface::class);
        $decoratedProphecy->normalize('fooBar', null, null, [])->willReturn('foo_bar')->shouldBeCalled();
        $decoratedProphecy->normalize('bazQux', null, null, [])->willReturn('baz_qux')->shouldBeCalled();

        $innerFieldsNameConverter = new InnerFieldsNameConverter($decoratedProphecy->reveal());

        self::assertSame('foo_bar.baz_qux', $innerFieldsNameConverter->normalize('fooBar.bazQux'));
    }

    public function testDenormalize()
    {
        $decoratedProphecy = $this->prophesize(AdvancedNameConverterInterface::class);
        $decoratedProphecy->denormalize('foo_bar', null, null, [])->willReturn('fooBar')->shouldBeCalled();
        $decoratedProphecy->denormalize('baz_qux', null, null, [])->willReturn('bazQux')->shouldBeCalled();

        $innerFieldsNameConverter = new InnerFieldsNameConverter($decoratedProphecy->reveal());

        self::assertSame('fooBar.bazQux', $innerFieldsNameConverter->denormalize('foo_bar.baz_qux'));
    }
}
