<?php

namespace ApiPlatform\Laravel\Console\Maker\Utils;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final readonly class AppServiceProviderTagger
{
    /** @var string  */
    private const SERVICE_PROVIDER_PATH = 'Providers/AppServiceProvider.php';

    /** @var string  */
    private const ITEM_PROVIDER_USE_STATEMENT = 'use ApiPlatform\Laravel\Eloquent\State\ItemProvider;';

    public function __construct(private Filesystem $filesystem) {}

    /**
     * @throws FileNotFoundException
     */
    public function addTagToServiceProvider(string $providerName): void
    {
        $serviceProviderPath = app_path(self::SERVICE_PROVIDER_PATH);

        $this->ensureServiceProviderExists($serviceProviderPath);

        $serviceProviderContent = $this->filesystem->get($serviceProviderPath);

        $this->addUseStatement($serviceProviderContent, self::ITEM_PROVIDER_USE_STATEMENT);
        $this->addUseStatement($serviceProviderContent, $this->getProviderNamespace($providerName));
        $this->addTag($serviceProviderContent, $providerName, $serviceProviderPath);
    }

    private function ensureServiceProviderExists(string $path): void
    {
        if (!$this->filesystem->exists($path)) {
            throw new \RuntimeException("The AppServiceProvider is missing!");
        }
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

    private function addTag(string &$content, string $providerName, string $serviceProviderPath): void
    {
        $tagStatement = sprintf("\n\n\t\t\$this->app->tag(%s::class, ItemProvider::class);", $providerName);
        if (!str_contains($content, $tagStatement)) {
            $content = preg_replace(
                '/(public function register\(\)[^{]*{)(.*?)(\s*}\s*})/s',
                "$1$2$tagStatement$3",
                $content
            );

            $this->filesystem->put($serviceProviderPath, $content);
        }
    }

    public function getProviderNamespace(string $providerName): string
    {
        return sprintf('use App\\State\\%s;', $providerName);
    }
}
