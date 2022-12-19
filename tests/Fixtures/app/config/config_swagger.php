<?php
declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Config\ApiPlatformConfig;

return static function (ApiPlatformConfig $apiPlatformConfig): void {
    $apiPlatformConfig->swagger()->apiKeys('Some Authorization Name')
        ->name('Authorization')
        ->type('header');
};
