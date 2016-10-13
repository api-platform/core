<?php

$finder = Symfony\CS\Finder::create()
    ->in(__DIR__)
    ->exclude('tests/Fixtures/app/cache')
;

return Symfony\CS\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        '-psr0',
        'ordered_use',
        'phpdoc_order',
        'short_array_syntax',
    ])
    ->finder($finder)
    ->setUsingCache(true)
;
