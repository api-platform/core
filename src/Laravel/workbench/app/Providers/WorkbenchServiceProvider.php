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
use Orchestra\Testbench\Workbench\Actions\AddAssetSymlinkFolders;
use Orchestra\Testbench\Workbench\Workbench;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Workbench\App\Services\DummyService;
use Workbench\App\State\CustomProviderWithDependency;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $config = $this->app['config'];
        $config->set('api-platform.resources', [app_path('Models'), app_path('ApiResource')]);
        $config->set('cache.default', 'null');

        $this->app->singleton(DummyService::class, fn ($app) => new DummyService($app['config']->get('api-platform.title')));
        $this->app->singleton(CustomProviderWithDependency::class, fn ($app) => new CustomProviderWithDependency($app->make(DummyService::class)));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $assets = new AddAssetSymlinkFolders($this->app->make(Filesystem::class), Workbench::configuration());
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $assets->handle(new ServeCommandStarted($input, $output, new Factory(new OutputStyle($input, $output))));
    }
}
