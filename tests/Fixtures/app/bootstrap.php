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

use Doctrine\Common\Annotations\AnnotationRegistry;

date_default_timezone_set('UTC');

// PHPUnit's autoloader
// This glob hack will not be necessary anymore when https://github.com/symfony/symfony/pull/37974 will have been merged.
$phpunitInstalls = glob(__DIR__.'/../../../vendor/bin/.phpunit/phpunit-*');
if (!$phpunitInstalls) {
    die('PHPUnit is not installed. Please run ./vendor/bin/simple-phpunit to install it');
}

// Use the most recent version available
require end($phpunitInstalls).'/vendor/autoload.php';

$loader = require __DIR__.'/../../../vendor/autoload.php';
require __DIR__.'/AppKernel.php';

AnnotationRegistry::registerLoader('class_exists');

return $loader;
