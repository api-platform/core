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
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\PHPUnit\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\Rector\MethodCall\AssertEqualsToSameRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\ArrayShapeFromConstantArrayReturnRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);
    $rectorConfig->skip([
        __DIR__.'/tests/Fixtures/app/var',
        AddSeeTestAnnotationRector::class,
        ArrayShapeFromConstantArrayReturnRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class => [
            __DIR__.'/tests/Fixtures/TestBundle/Document',
            __DIR__.'/tests/Fixtures/TestBundle/Entity',
        ],
        RemoveParentCallWithoutParentRector::class => [
            __DIR__.'/tests/Symfony/Validator/Metadata/Property/Restriction/PropertySchemaChoiceRestrictionTest.php',
        ],
        RenameMethodRector::class => [
            __DIR__.'/tests/Symfony/Validator/Metadata/Property/Restriction/PropertySchemaChoiceRestrictionTest.php',
        ],
        AssertEqualsToSameRector::class,
    ]);
    $rectorConfig->phpstanConfig(__DIR__.'/phpstan.neon.dist');
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::TYPE_DECLARATION_STRICT,
    ]);

    // PHPUnit
    $rectorConfig->sets([
        PHPUnitSetList::PHPUNIT_91,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_EXCEPTION,
        PHPUnitSetList::REMOVE_MOCKS,
        PHPUnitSetList::PHPUNIT_SPECIFIC_METHOD,
        PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER,
    ]);
};
