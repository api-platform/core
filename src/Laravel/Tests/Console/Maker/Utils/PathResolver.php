<?php

namespace ApiPlatform\Laravel\Tests\Console\Maker\Utils;

final readonly class PathResolver
{
    public function getServiceProviderFilePath(): string
    {
        return base_path('app/Providers/AppServiceProvider.php');
    }

    public function generateStateProviderFilename(string $providerName): string
    {
        return $this->getStateDirectoryPath() . $providerName . '.php';
    }

    public function getStateDirectoryPath(): string
    {
        return base_path('src/State/');
    }
}
