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

namespace ApiPlatform\Tests;

use Symfony\Component\Routing\Router;

trait SetupClassResourcesTrait
{
    use WithResourcesTrait;

    public static function setUpBeforeClass(): void
    {
        static::writeResources(self::getResources());
    }

    public static function tearDownAfterClass(): void
    {
        static::removeResources();
        $reflectionClass = new \ReflectionClass(Router::class);
        $reflectionClass->setStaticPropertyValue('cache', []);
    }

    /**
     * @return class-string[]
     */
    abstract public static function getResources(): array;
}
