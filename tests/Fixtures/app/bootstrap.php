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

// Increase default max nesting level allowed by XDebug for the Symfony container
$xdebugMaxNestingLevel = ini_get('xdebug.max_nesting_level');
if (false !== $xdebugMaxNestingLevel && $xdebugMaxNestingLevel <= 512) {
    ini_set('xdebug.max_nesting_level', 512);
}

$loader = require __DIR__.'/../../../vendor/autoload.php';
require __DIR__.'/AppKernel.php';

return $loader;
