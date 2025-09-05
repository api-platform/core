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

final readonly class FilterAppServiceProviderTagger
{
    /** @var string */
    private const APP_SERVICE_PROVIDER_PATH = 'Providers/AppServiceProvider.php';

    /** @var string */
    private const FILTER_INTERFACE_USE_STATEMENT = 'use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface;';

    public function __construct(private Filesystem $filesystem)
    {
    }

    /**
     * @throws FileNotFoundException
     */
    public function addTagToServiceProvider(string $filterName): void
    {
        $appServiceProviderPath = app_path(self::APP_SERVICE_PROVIDER_PATH);
        if (!$this->filesystem->exists($appServiceProviderPath)) {
            throw new \RuntimeException('The AppServiceProvider is missing!');
        }

        $serviceProviderContent = $this->filesystem->get($appServiceProviderPath);

        $this->addUseStatements($serviceProviderContent, $filterName);
        $this->addTag($serviceProviderContent, $filterName, $appServiceProviderPath);
    }

    private function addUseStatements(string &$content, string $filterName): void
    {
        $useStatements = [self::FILTER_INTERFACE_USE_STATEMENT, \sprintf('use App\\Filter\\%s;', $filterName)];
        $statementsString = implode("\n", $useStatements)."\n";

        $content = preg_replace(
            '/^(namespace\s[^;]+;\s*)/m',
            "$1\n$statementsString",
            $content,
            1
        );
    }

    private function addTag(string &$content, string $filterName, string $serviceProviderPath): void
    {
        $tagStatement = \sprintf("\n\n\t\t\$this->app->tag(%s::class, FilterInterface::class);", $filterName);

        if (!str_contains($content, $tagStatement)) {
            $content = preg_replace(
                '/(public function register\(\)[^{]*{)(.*?)(\s*}\s*})/s',
                "$1$2$tagStatement$3",
                $content
            );

            $this->filesystem->put($serviceProviderPath, $content);
        }
    }
}
