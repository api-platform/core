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

namespace ApiPlatform\Validator\Tests\Util;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Validator\Util\ParameterValidationConstraints;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;

final class ParameterValidationConstraintsTest extends TestCase
{
    #[DataProvider('provideZeroBounds')]
    public function testZeroBounds(array $schema, Constraint $expected): void
    {
        $validation = new class {
            use ParameterValidationConstraints;
        };

        $this->assertContainsEquals($expected, $validation::getParameterValidationConstraints(new QueryParameter(schema: $schema)));
    }

    public static function provideZeroBounds(): iterable
    {
        yield 'minimum and maximum' => [['type' => 'integer', 'minimum' => 0, 'maximum' => 100], new Range(min: 0, max: 100)];
        yield 'minimum only' => [['type' => 'integer', 'minimum' => 0], new GreaterThanOrEqual(value: 0)];
        yield 'maximum only' => [['type' => 'integer', 'maximum' => 0], new LessThanOrEqual(value: 0)];
        yield 'zero maximum with a minimum' => [['type' => 'integer', 'minimum' => -10, 'maximum' => 0], new Range(min: -10, max: 0)];
        yield 'exclusive minimum and maximum' => [['type' => 'integer', 'exclusiveMinimum' => 0, 'maximum' => 100], new GreaterThan(value: 0)];
        yield 'minimum and exclusive maximum' => [['type' => 'integer', 'minimum' => -10, 'exclusiveMaximum' => 0], new LessThan(value: 0)];
        yield 'exclusive minimum only' => [['type' => 'integer', 'exclusiveMinimum' => 0], new GreaterThan(value: 0)];
        yield 'exclusive maximum only' => [['type' => 'integer', 'exclusiveMaximum' => 0], new LessThan(value: 0)];
    }
}
