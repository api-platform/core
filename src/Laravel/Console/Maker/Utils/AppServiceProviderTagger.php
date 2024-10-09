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

final readonly class AppServiceProviderTagger
{
    /** @var string */
    private const APP_SERVICE_PROVIDER_PATH = 'Providers/AppServiceProvider.php';

    /** @var string */
    private const ITEM_PROVIDER_USE_STATEMENT = 'use ApiPlatform\State\ProviderInterface;';

    /** @var string */
    private const ITEM_PROCESSOR_USE_STATEMENT = 'use ApiPlatform\State\ProcessorInterface;';

    public function __construct(private Filesystem $filesystem)
    {
    }

    /**
     * @throws FileNotFoundException
     */
    public function addTagToServiceProvider(string $providerName, StateTypeEnum $stateTypeEnum): void
    {
        $appServiceProviderPath = app_path(self::APP_SERVICE_PROVIDER_PATH);
        if (!$this->filesystem->exists($appServiceProviderPath)) {
            throw new \RuntimeException('The AppServiceProvider is missing!');
        }

        $serviceProviderContent = $this->filesystem->get($appServiceProviderPath);

        $this->addUseStatement($serviceProviderContent, $this->getStateTypeStatement($stateTypeEnum));
        $this->addUseStatement($serviceProviderContent, \sprintf('use App\\State\\%s;', $providerName));
        $this->addTag($serviceProviderContent, $providerName, $appServiceProviderPath, $stateTypeEnum);
    }

    private function addUseStatement(string &$content, string $useStatement): void
    {
        if (!str_contains($content, $useStatement)) {
            $content = preg_replace(
                '/^(namespace\s[^;]+;\s*)(\n)/m',
                "$1\n$useStatement$2",
                $content,
                1
            );
        }
    }

    private function addTag(string &$content, string $stateName, string $serviceProviderPath, StateTypeEnum $stateTypeEnum): void
    {
        $tagStatement = \sprintf("\n\n\t\t\$this->app->tag(%s::class, %sInterface::class);", $stateName, $stateTypeEnum->name);

        if (!str_contains($content, $tagStatement)) {
            $content = preg_replace(
                '/(public function register\(\)[^{]*{)(.*?)(\s*}\s*})/s',
                "$1$2$tagStatement$3",
                $content
            );

            $this->filesystem->put($serviceProviderPath, $content);
        }
    }

    private function getStateTypeStatement(StateTypeEnum $stateTypeEnum): string
    {
        return match ($stateTypeEnum) {
            StateTypeEnum::Provider => self::ITEM_PROVIDER_USE_STATEMENT,
            StateTypeEnum::Processor => self::ITEM_PROCESSOR_USE_STATEMENT,
        };
    }
}
