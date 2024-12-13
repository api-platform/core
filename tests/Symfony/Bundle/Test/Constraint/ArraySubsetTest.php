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

namespace ApiPlatform\Tests\Symfony\Bundle\Test\Constraint;

use ApiPlatform\Symfony\Bundle\Test\Constraint\ArraySubset;
use ApiPlatform\Tests\Fixtures\ArrayAccessible;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Framework\TestCase;

/**
 * Imported from dms/phpunit-arraysubset-asserts, because the original constraint has been deprecated.
 *
 * @copyright Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright Rafael Dohms <rdohms@gmail.com>
 *
 * @see https://github.com/sebastianbergmann/phpunit/issues/3494
 */
class ArraySubsetTest extends TestCase
{
    public static function evaluateDataProvider(): array
    {
        return [
            'loose array subset and array other' => [
                'expected' => true,
                'subset' => ['bar' => 0],
                'other' => ['foo' => '', 'bar' => '0'],
                'strict' => false,
            ],
            'strict array subset and array other' => [
                'expected' => false,
                'subset' => ['bar' => 0],
                'other' => ['foo' => '', 'bar' => '0'],
                'strict' => true,
            ],
            'loose array subset and ArrayObject other' => [
                'expected' => true,
                'subset' => ['bar' => 0],
                'other' => new \ArrayObject(['foo' => '', 'bar' => '0']),
                'strict' => false,
            ],
            'strict ArrayObject subset and array other' => [
                'expected' => true,
                'subset' => new \ArrayObject(['bar' => 0]),
                'other' => ['foo' => '', 'bar' => 0],
                'strict' => true,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('evaluateDataProvider')]
    public function testEvaluate(bool $expected, iterable $subset, iterable $other, bool $strict): void
    {
        $constraint = new ArraySubset($subset, $strict);

        $this->assertSame($expected, $constraint->evaluate($other, '', true));
    }

    public function testEvaluateWithArrayAccess(): void
    {
        $arrayAccess = new ArrayAccessible(['foo' => 'bar']);

        $constraint = new ArraySubset(['foo' => 'bar']);

        $this->assertTrue($constraint->evaluate($arrayAccess, '', true));
    }

    public function testEvaluateFailMessage(): void
    {
        $constraint = new ArraySubset(['foo' => 'bar']);

        try {
            $constraint->evaluate(['baz' => 'bar'], '', false);
            $this->fail(\sprintf('Expected %s to be thrown.', ExpectationFailedException::class));
        } catch (ExpectationFailedException $expectedException) {
            $comparisonFailure = $expectedException->getComparisonFailure();
            $this->assertNotNull($comparisonFailure);
            $this->assertStringContainsString("'foo' => 'bar'", $comparisonFailure->getExpectedAsString());
            $this->assertStringContainsString("'baz' => 'bar'", $comparisonFailure->getActualAsString());
        }
    }

    public function testIsCountable(): void
    {
        $reflection = new \ReflectionClass(ArraySubset::class);

        $this->assertTrue(
            $reflection->implementsInterface(\Countable::class),
            \sprintf(
                'Failed to assert that ArraySubset implements "%s".',
                \Countable::class
            )
        );
    }

    public function testIsSelfDescribing(): void
    {
        $reflection = new \ReflectionClass(ArraySubset::class);

        $this->assertTrue(
            $reflection->implementsInterface(SelfDescribing::class),
            \sprintf(
                'Failed to assert that Array implements "%s".',
                SelfDescribing::class
            )
        );
    }
}
