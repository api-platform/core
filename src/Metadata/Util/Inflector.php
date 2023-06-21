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

namespace ApiPlatform\Metadata\Util;

use Doctrine\Inflector\Inflector as LegacyInflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\UnicodeString;

/**
 * @internal
 */
final class Inflector
{
    private static ?LegacyInflector $instance = null;

    private static function getInstance(): LegacyInflector
    {
        return self::$instance
            ?? self::$instance = InflectorFactory::create()->build();
    }

    /**
     * @see InflectorObject::tableize()
     */
    public static function tableize(string $word): string
    {
        if (class_exists(UnicodeString::class)) {
            return (new UnicodeString($word))->snake()->toString();
        }

        return self::getInstance()->tableize($word);
    }

    /**
     * @see InflectorObject::pluralize()
     */
    public static function pluralize(string $word): string
    {
        if (class_exists(EnglishInflector::class)) {
            return (new EnglishInflector())->pluralize($word)[0];
        }

        return self::getInstance()->pluralize($word);
    }
}
