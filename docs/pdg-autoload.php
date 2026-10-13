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

/*
 * Autoloader used by pdg-phpunit to run the guides, see PDG_AUTOLOAD.
 *
 * ApiTestCase moved to the api-platform/test package, which the guides do not require:
 * that package requires PHPUnit ^11.5 || ^12.2, while pdg-phpunit runs on the PHPUnit
 * version installed here. Map the namespace on the monorepo sources instead.
 *
 * The pinned pdg-phpunit revision also hardcodes the pre-5.0 ApiTestCase FQCN in its own
 * PlaygroundTestCase harness, hence the alias, which can go once pdg is updated.
 */

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/vendor/autoload.php';
$loader->addPsr4('ApiPlatform\\Test\\', __DIR__.'/../src/Test');

if (!class_exists(ApiPlatform\Symfony\Bundle\Test\ApiTestCase::class, false)) {
    class_alias(ApiPlatform\Test\ApiTestCase::class, ApiPlatform\Symfony\Bundle\Test\ApiTestCase::class);
}
