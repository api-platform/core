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
use ApiPlatform\Laravel\Console\Maker\Utils\StateTemplateGenerator;
use ApiPlatform\Laravel\Console\Maker\Utils\StateTypeEnum;
use ApiPlatform\Laravel\Console\Maker\Utils\SuccessMessageTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

abstract class AbstractMakeStateCommand extends Command
{
    use SuccessMessageTrait;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly StateTemplateGenerator $stateTemplateGenerator,
        private readonly AppServiceProviderTagger $appServiceProviderTagger,
    ) {
        parent::__construct();
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $stateName = $this->askForStateName();

        $directoryPath = base_path('app/State/');
        $this->filesystem->ensureDirectoryExists($directoryPath);

        $filePath = $this->stateTemplateGenerator->getFilePath($directoryPath, $stateName);
        if ($this->filesystem->exists($filePath)) {
            $this->error(\sprintf('[ERROR] The file "%s" can\'t be generated because it already exists.', $filePath));

            return self::FAILURE;
        }

        $this->stateTemplateGenerator->generate($filePath, $stateName, $this->getStateType());
        if (!$this->filesystem->exists($filePath)) {
            $this->error(\sprintf('[ERROR] The file "%s" could not be created.', $filePath));

            return self::FAILURE;
        }

        $this->appServiceProviderTagger->addTagToServiceProvider($stateName, $this->getStateType());

        $this->writeSuccessMessage($filePath, $this->getStateType());

        return self::SUCCESS;
    }

    protected function askForStateName(): string
    {
        do {
            $stateType = $this->getStateType()->name;
            $stateName = $this->ask(\sprintf('Choose a class name for your state %s (e.g. <fg=yellow>AwesomeState%s</>)', strtolower($stateType), ucfirst($stateType)));
            if (empty($stateName)) {
                $this->error('[ERROR] This value cannot be blank.');
            }
        } while (empty($stateName));

        return $stateName;
    }

    abstract protected function getStateType(): StateTypeEnum;
}
