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

namespace Workbench\App\Providers;

use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\Foundation\Events\ServeCommandStarted;
use Orchestra\Testbench\Workbench\Workbench;
use Orchestra\Workbench\Listeners\AddAssetSymlinkFolders;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app['config']->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $assets = new AddAssetSymlinkFolders(Workbench::configuration(), $this->app->make(Filesystem::class));
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $assets->handle(new ServeCommandStarted($input, $output, new Factory(new OutputStyle($input, $output))));
    }
}
