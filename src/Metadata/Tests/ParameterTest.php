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

namespace ApiPlatform\Metadata\Tests;

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\ParameterNotFound;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testDefaultValue(): void
    {
        $this->assertInstanceOf(ParameterNotFound::class, (new QueryParameter())->getValue());
    }

    #[IgnoreDeprecations]
    public function testSetValueIsDeprecated(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 5.1: Method "ApiPlatform\Metadata\Parameter::setValue()" is deprecated and will be removed in 6.0. Parameters are immutable: return "$parameter->withValue($value)" (or an operation holding it) from your parameter provider instead.');

        (new QueryParameter())->setValue('a');
    }

    #[DataProvider('provideDefaultValueCases')]
    public function testDefaultValueWithFallbackValue(Parameter $parameter, mixed $fallbackValue, mixed $expectedDefault): void
    {
        $this->assertSame($expectedDefault, $parameter->getValue($fallbackValue));
    }

    /** @return iterable<array{Parameter, mixed, mixed}> */
    public static function provideDefaultValueCases(): iterable
    {
        $fallbackValue = new ParameterNotFound();

        yield 'no default specified' => [
            new QueryParameter(), $fallbackValue, $fallbackValue,
        ];

        yield 'with a default specified' => [
            new QueryParameter(default: false), $fallbackValue, false,
        ];

        yield 'with null as default' => [
            new QueryParameter(default: null), $fallbackValue,  $fallbackValue,
        ];
    }
}
