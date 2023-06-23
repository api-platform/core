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

namespace ApiPlatform\Util;

use Doctrine\Inflector\Inflector as LegacyInflector;
use Doctrine\Inflector\InflectorFactory;

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
        return self::getInstance()->tableize($word);
    }

    /**
     * @see InflectorObject::pluralize()
     */
    public static function pluralize(string $word): string
    {
        return self::getInstance()->pluralize($word);
    }
}
