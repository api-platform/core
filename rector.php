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

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);
    $rectorConfig->skip([
        // "Class PHPUnit\Framework\* not found."
        __DIR__.'/src/Symfony/Bundle/Test',
        __DIR__.'/tests/Fixtures/app/var',
        __DIR__.'/tests/ProphecyTrait.php',
    ]);
    $rectorConfig->phpstanConfig(__DIR__.'/phpstan.neon.dist');
    $rectorConfig->importNames();
    $rectorConfig->disableImportShortClasses();

    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::TYPE_DECLARATION_STRICT,
    ]);

    // PHPUnit
//    $rectorConfig->sets([
//        PHPUnitSetList::PHPUNIT_91,
//        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
//        PHPUnitSetList::PHPUNIT_EXCEPTION,
//        PHPUnitSetList::REMOVE_MOCKS,
//        PHPUnitSetList::PHPUNIT_SPECIFIC_METHOD,
//        PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER,
//    ]);
};
