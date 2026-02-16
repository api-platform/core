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

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/src/Core/Bridge/Symfony/Maker/Resources/skeleton',
        __DIR__.'/src/Laravel/Console/Maker/Resources/skeleton',
        __DIR__.'/src/Laravel/config',
        __DIR__.'/tests/Fixtures/app/var',
        __DIR__.'/docs/guides',
        __DIR__.'/docs/var',
        __DIR__.'/src/Doctrine/Orm/Tests/var',
        __DIR__.'/src/Doctrine/Odm/Tests/var',
        __DIR__.'/tests/Fixtures/app/config/reference.php',
        __DIR__.'/src/Symfony/Bundle/DependencyInjection/Configuration.php',
        __DIR__.'/tests/Fixer/SymfonyServiceClassConstantFixer.php',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withPreparedSets(
        typeDeclarations: true,
    )
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
