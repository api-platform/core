<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Behat\Context\Context;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Clears the Symfony cache before and after suites.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class CacheContext implements Context
{
    /**
     * @BeforeSuite
     * @AfterSuite
     */
    public static function cleanCache()
    {
        $fs = new Filesystem();
        $cacheDir = __DIR__.'/../../tests/Fixtures/app/cache';

        if ($fs->exists($cacheDir)) {
            $fs->remove($cacheDir);
        }
    }
}
