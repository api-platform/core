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
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testDefaultValue(): void
    {
        $this->assertInstanceOf(ParameterNotFound::class, (new QueryParameter())->getValue());
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
