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

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final readonly class FilterTemplateGenerator
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function getFilePath(string $directoryPath, string $filterFileName): string
    {
        return \sprintf('%s%s.php', $directoryPath, $filterFileName);
    }

    /**
     * @throws FileNotFoundException
     */
    public function generate(string $pathLink, string $filterName): void
    {
        $namespace = 'App\\Filter';
        $template = $this->filesystem->get(
            \sprintf(
                '%s/Resources/skeleton/EloquentFilter.php.tpl',
                \dirname(__DIR__),
            )
        );

        $content = strtr($template, [
            '{{ namespace }}' => $namespace,
            '{{ class_name }}' => $filterName,
        ]);

        $this->filesystem->put($pathLink, $content);
    }
}
