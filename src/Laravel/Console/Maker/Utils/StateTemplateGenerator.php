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

final readonly class StateTemplateGenerator
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function getFilePath(string $directoryPath, string $stateFileName): string
    {
        return $directoryPath.$stateFileName.'.php';
    }

    /**
     * @throws FileNotFoundException
     */
    public function generate(string $pathLink, string $stateClassName, StateTypeEnum $stateTypeEnum): void
    {
        $namespace = 'App\\State';
        $template = $this->loadTemplate($stateTypeEnum);

        $content = strtr($template, [
            '{{ namespace }}' => $namespace,
            '{{ class_name }}' => $stateClassName,
        ]);

        $this->filesystem->put($pathLink, $content);
    }

    /**
     * @throws FileNotFoundException
     */
    private function loadTemplate(StateTypeEnum $stateTypeEnum): string
    {
        $templateFile = match ($stateTypeEnum) {
            StateTypeEnum::Provider => 'StateProvider.php.tpl',
            StateTypeEnum::Processor => 'StateProcessor.php.tpl',
        };

        $templatePath = \dirname(__DIR__).'/Resources/skeleton/'.$templateFile;

        return $this->filesystem->get($templatePath);
    }
}
