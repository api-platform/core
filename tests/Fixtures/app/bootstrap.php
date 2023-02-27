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

date_default_timezone_set('UTC');

// PHPUnit's autoloader
if (!file_exists($phpUnitAutoloaderPath = __DIR__.'/../../../vendor/bin/.phpunit/phpunit/vendor/autoload.php')) {
    exit('PHPUnit is not installed. Please run ./vendor/bin/simple-phpunit to install it');
}

$phpunitLoader = require $phpUnitAutoloaderPath;
// Don't register the PHPUnit autoloader before the normal autoloader to prevent weird issues
$phpunitLoader->unregister();
$phpunitLoader->register();

$loader = require __DIR__.'/../../../vendor/autoload.php';
require __DIR__.'/AppKernel.php';

return $loader;
