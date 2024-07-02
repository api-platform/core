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

use ApiPlatform\Metadata\InflectorInterface;
use Doctrine\Inflector\Inflector as LegacyInflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Component\String\Inflector\EnglishInflector;
use Symfony\Component\String\UnicodeString;

/**
 * @internal
 */
final class Inflector implements InflectorInterface
{
    public function __construct(private bool $keepLegacyInflector = true)
    {
    }

    private static ?LegacyInflector $instance = null;

    private static function getInstance(): LegacyInflector
    {
        return static::$instance
            ?? static::$instance = InflectorFactory::create()->build();
    }

    /**
     * @see InflectorObject::tableize()
     */
    public function tableize(string $word): string
    {
        if (!$this->keepLegacyInflector) {
            return (new UnicodeString($word))->snake()->toString();
        }

        return static::getInstance()->tableize($word);
    }

    /**
     * @see InflectorObject::pluralize()
     */
    public function pluralize(string $word): string
    {
        if (!$this->keepLegacyInflector) {
            $pluralize = (new EnglishInflector())->pluralize($word);

            return end($pluralize);
        }

        return self::getInstance()->pluralize($word);
    }
}
