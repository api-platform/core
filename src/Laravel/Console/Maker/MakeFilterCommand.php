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

use ApiPlatform\Laravel\Console\Maker\Utils\FilterAppServiceProviderTagger;
use ApiPlatform\Laravel\Console\Maker\Utils\FilterTemplateGenerator;
use ApiPlatform\Laravel\Console\Maker\Utils\SuccessMessageTrait;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final class MakeFilterCommand extends Command
{
    use SuccessMessageTrait;

    protected $signature = 'make:filter';
    protected $description = 'Creates an API Platform filter';

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly FilterTemplateGenerator $filterTemplateGenerator,
        private readonly FilterAppServiceProviderTagger $filterAppServiceProviderTagger,
    ) {
        parent::__construct();
    }

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $nameArgument = $this->ask('Choose a class name for your filter (e.g. <fg=yellow>AwesomeFilter</>)');
        if (null === $nameArgument || '' === $nameArgument) {
            $this->error('[ERROR] The name argument cannot be blank.');

            return self::FAILURE;
        }

        $directoryPath = base_path('app/Filter/');
        $this->filesystem->ensureDirectoryExists($directoryPath);

        $filePath = $this->filterTemplateGenerator->getFilePath($directoryPath, $nameArgument);
        if ($this->filesystem->exists($filePath)) {
            $this->error(\sprintf('[ERROR] The file "%s" can\'t be generated because it already exists.', $filePath));

            return self::FAILURE;
        }

        $this->filterTemplateGenerator->generate($filePath, $nameArgument);
        if (!$this->filesystem->exists($filePath)) {
            $this->error(\sprintf('[ERROR] The file "%s" could not be created.', $filePath));

            return self::FAILURE;
        }

        $this->filterAppServiceProviderTagger->addTagToServiceProvider($nameArgument);

        $this->writeSuccessMessage($filePath, 'Eloquent Filter');

        return self::SUCCESS;
    }
}
