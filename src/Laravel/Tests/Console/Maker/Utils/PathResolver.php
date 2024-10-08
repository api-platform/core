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

namespace ApiPlatform\Laravel\Tests\Console\Maker\Utils;

final readonly class PathResolver
{
    public function getServiceProviderFilePath(): string
    {
        return base_path('app/Providers/AppServiceProvider.php');
    }

    public function generateStateProviderFilename(string $providerName): string
    {
        return $this->getStateDirectoryPath().$providerName.'.php';
    }

    public function getStateDirectoryPath(): string
    {
        return base_path('src/State/');
    }
}
