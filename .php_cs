<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
    ->exclude('features/fixtures/TestApp/cache')
    ->exclude('features/fixtures/TestAppNormalizeRelations/cache')
;

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->finder($finder)
;
