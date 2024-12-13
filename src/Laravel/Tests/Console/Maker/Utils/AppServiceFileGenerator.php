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

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final readonly class AppServiceFileGenerator
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    /**
     * @throws FileNotFoundException
     */
    public function regenerateProviderFile(): void
    {
        $templatePath = \dirname(__DIR__).'/Resources/skeleton/AppServiceProvider.php.tpl';
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
