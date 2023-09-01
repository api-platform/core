<?php
require '../vendor/autoload.php';

use ApiPlatform\Playground\Kernel;
use Symfony\Component\HttpFoundation\Request;

$guide = $_SERVER['APP_GUIDE'] ?? $_ENV['APP_GUIDE'] ?? 'declare-a-resource';

if (!$guide) {
    throw new \RuntimeException('No guide.');
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
$app->handle(Request::create('/docs.json'))->getContent();
