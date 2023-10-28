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

namespace ApiPlatform\Elasticsearch\Tests\Serializer\NameConverter;

use ApiPlatform\Elasticsearch\Serializer\NameConverter\InnerFieldsNameConverter;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

class InnerFieldsNameConverterTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        self::assertInstanceOf(
            AdvancedNameConverterInterface::class,
            new InnerFieldsNameConverter($this->prophesize(AdvancedNameConverterInterface::class)->reveal())
        );
    }

    public function testNormalize(): void
    {
        $innerProphecy = $this->prophesize(AdvancedNameConverterInterface::class);
        $innerProphecy->normalize('fooBar', null, null, [])->willReturn('foo_bar')->shouldBeCalled();
        $innerProphecy->normalize('bazQux', null, null, [])->willReturn('baz_qux')->shouldBeCalled();

        $innerFieldsNameConverter = new InnerFieldsNameConverter($innerProphecy->reveal());

        self::assertSame('foo_bar.baz_qux', $innerFieldsNameConverter->normalize('fooBar.bazQux'));
    }

    public function testDenormalize(): void
    {
        $innerProphecy = $this->prophesize(AdvancedNameConverterInterface::class);
        $innerProphecy->denormalize('foo_bar', null, null, [])->willReturn('fooBar')->shouldBeCalled();
        $innerProphecy->denormalize('baz_qux', null, null, [])->willReturn('bazQux')->shouldBeCalled();

        $innerFieldsNameConverter = new InnerFieldsNameConverter($innerProphecy->reveal());

        self::assertSame('fooBar.bazQux', $innerFieldsNameConverter->denormalize('foo_bar.baz_qux'));
    }
}
