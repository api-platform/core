<?php

// See https://github.com/phpstan/phpstan-doctrine#configuration

$kernel = new AppKernel($_SERVER['APP_ENV'] ?? 'test', (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();
return $kernel->getContainer()->get('doctrine')->getManager();
