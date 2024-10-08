<?php

namespace ApiPlatform\Laravel\Console\Maker\Utils;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final readonly class StateProviderGenerator
{
    public function __construct(private Filesystem $filesystem) {}

    public function getFilePath(string $directoryPath, string $providerName): string
    {
        return $directoryPath . $providerName . '.php';
    }

    public function isFileExists(string $filePath): bool
    {
        return $this->filesystem->exists($filePath);
    }

    /**
     * @throws FileNotFoundException
     */
    public function generate(string $pathLink, string $providerName): void
    {
        $namespace = 'App\\State';
        $template = $this->loadTemplate();

        $content = $this->replacePlaceholders($template, [
            '{{ namespace }}' => $namespace,
            '{{ class_name }}' => $providerName,
        ]);

        $this->filesystem->put($pathLink, $content);
    }

    /**
     * @throws FileNotFoundException
     */
    private function loadTemplate(): string
    {
        $templatePath = dirname(__DIR__) . '/Resources/skeleton/StateProvider.tpl.php';

        return $this->filesystem->get($templatePath);
    }

    private function replacePlaceholders(string $template, array $variables): string
    {
        return strtr($template, $variables);
    }
}
