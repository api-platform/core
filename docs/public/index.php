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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('date.timezone', 'Europe/Paris');

require __DIR__.'/../vendor/autoload.php';

use ApiPlatform\Playground\Kernel;
use RuntimeException;

if (!($guide = $_SERVER['APP_GUIDE'] ?? $_ENV['APP_GUIDE'] ?? null)) {
    throw new RuntimeException('No guide.');
}

$app = function (array $context) use ($guide) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG'], $guide);
};

$runtime = $_SERVER['APP_RUNTIME'] ?? $_ENV['APP_RUNTIME'] ?? 'Symfony\\Component\\Runtime\\SymfonyRuntime';
$runtime = new $runtime(['disable_dotenv']);
[$app, $args] = $runtime
    ->getResolver($app)
    ->resolve();

$app = $app(...$args);
$app->executeMigrations();
$app->loadFixtures();
$app->request();
