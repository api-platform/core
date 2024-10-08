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

namespace ApiPlatform\Laravel\Console\Maker\Utils;

use Illuminate\Filesystem\Filesystem;

final readonly class StateDirectoryManager
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function ensureStateDirectoryExists(): string
    {
        $directoryPath = base_path('src/State/');
        $this->filesystem->ensureDirectoryExists($directoryPath);

        return $directoryPath;
    }
}
