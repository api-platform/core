<?php

namespace ApiPlatform\Laravel\Tests\Console\Maker\Utils;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final readonly class AppServiceFileGenerator
{

    public function __construct(private Filesystem $filesystem) {}

    /**
     * @throws FileNotFoundException
     */
    public function regenerateProviderFile(): void
    {
        $templatePath = dirname(__DIR__) . '/Resources/skeleton/AppServiceProvider.tpl.php';
        $targetPath = base_path('app/Providers/AppServiceProvider.php');

        $this->regenerateFileFromTemplate($templatePath, $targetPath);
    }

    /**
     * @throws FileNotFoundException
     */
    private function regenerateFileFromTemplate(string $templatePath, string $targetPath): void
    {
        $content = $this->filesystem->get($templatePath);

        $this->filesystem->put($targetPath, $content);
    }
}
