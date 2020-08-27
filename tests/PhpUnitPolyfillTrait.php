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

namespace ApiPlatform\Core\Tests;

use PHPUnit\Framework\Assert;

/**
 * Polyfill for PHPUnit's assertMatchesRegularExpression().
 * To remove when https://github.com/symfony/symfony/pull/37960 will be merged.
 */
trait PhpUnitPolyfillTrait
{
    /**
     * Asserts that a string matches a given regular expression.
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, $message = ''): void
    {
        if (method_exists(Assert::class, 'assertMatchesRegularExpression')) {
            Assert::assertMatchesRegularExpression($pattern, $string, $message);

            return;
        }

        // Fallback for PHPUnit 7
        self::assertRegExp($pattern, $string, $message);
    }
}
