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

namespace ApiPlatform\Laravel\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'api-platform:install')]
class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'api-platform:install';

    /**
     * @var string
     */
    protected $description = 'Install all of the API Platform resources';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->comment('Publishing API Platform Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'api-platform-assets']);

        $this->comment('Publishing API Platform Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'api-platform-config']);

        $this->info('API Platform installed successfully.');
    }
}
