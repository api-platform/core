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

namespace ApiPlatform\Laravel\Console\Maker;

use ApiPlatform\Laravel\Console\Maker\Utils\AppServiceProviderTagger;
use ApiPlatform\Laravel\Console\Maker\Utils\StateDirectoryManager;
use ApiPlatform\Laravel\Console\Maker\Utils\StateProviderGenerator;
use ApiPlatform\Laravel\Console\Maker\Utils\SuccessMessageTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final class MakeStateProviderCommand extends Command
{
    use SuccessMessageTrait;

    protected $signature = 'make:state-provider';

    protected $description = 'Creates an API Platform state provider';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly StateProviderGenerator $stateProviderGenerator,
        private readonly AppServiceProviderTagger $appServiceProviderTagger,
        private readonly StateDirectoryManager $stateDirectoryManager,
    ) {
        parent::__construct();
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $providerName = $this->askForProviderName();
        $directoryPath = $this->stateDirectoryManager->ensureStateDirectoryExists();

        $filePath = $this->stateProviderGenerator->getFilePath($directoryPath, $providerName);
        if ($this->stateProviderGenerator->isFileExists($filePath)) {
            $this->error(\sprintf('[ERROR] The file "%s" can\'t be generated because it already exists.', $filePath));

            return self::FAILURE;
        }

        $this->stateProviderGenerator->generate($filePath, $providerName);
        if (!$this->filesystem->exists($filePath)) {
            $this->error(\sprintf('[ERROR] The file "%s" could not be created.', $filePath));

            return self::FAILURE;
        }

        $this->appServiceProviderTagger->addTagToServiceProvider($providerName);

        $this->writeSuccessMessage($filePath);

        return self::SUCCESS;
    }

    private function askForProviderName(): string
    {
        do {
            $providerName = $this->ask('Choose a class name for your state provider (e.g. <fg=yellow>AwesomeStateProvider</>)');
            if (empty($providerName)) {
                $this->error('[ERROR] This value cannot be blank.');
            }
        } while (empty($providerName));

        return $providerName;
    }
}
