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

namespace ApiPlatform\Core\Util;

use ApiPlatform\Core\Exception\RuntimeException;
use Doctrine\Common\Inflector\Inflector as LegacyInflector;
use Doctrine\Inflector\Inflector as InflectorObject;
use Doctrine\Inflector\InflectorFactory;

/**
 * Facade for Doctrine Inflector.
 *
 * This class allows us to maintain compatibility with Doctrine Inflector 1.3 and 2.0 at the same time.
 *
 * @internal
 */
final class Inflector
{
    /**
     * @var InflectorObject
     */
    private static $instance;

    private static function getInstance(): InflectorObject
    {
        return self::$instance
            ?? self::$instance = InflectorFactory::create()->build();
    }

    /**
     * @see LegacyInflector::tableize()
     */
    public static function tableize(string $word): string
    {
        if (class_exists(InflectorFactory::class)) {
            return self::getInstance()->tableize($word);
        }

        if (class_exists(LegacyInflector::class)) {
            return LegacyInflector::tableize($word);
        }

        throw new RuntimeException('Unable to find a proper Doctrine Inflector instance.');
    }

    /**
     * @see LegacyInflector::pluralize()
     */
    public static function pluralize(string $word): string
    {
        if (class_exists(InflectorFactory::class)) {
            return self::getInstance()->pluralize($word);
        }

        if (class_exists(LegacyInflector::class)) {
            return LegacyInflector::pluralize($word);
        }

        throw new RuntimeException('Unable to find a proper Doctrine Inflector instance.');
    }
}
