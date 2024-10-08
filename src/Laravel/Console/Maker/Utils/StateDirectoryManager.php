<?php

namespace ApiPlatform\Laravel\Console\Maker\Utils;

use Illuminate\Filesystem\Filesystem;

final readonly class StateDirectoryManager
{
    public function __construct(private Filesystem $filesystem) {}

    public function ensureStateDirectoryExists(): string
    {
        $directoryPath = base_path('src/State/');
        $this->filesystem->ensureDirectoryExists($directoryPath);

        return $directoryPath;
    }
}
