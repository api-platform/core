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

namespace ApiPlatform\Laravel\Eloquent;

use ApiPlatform\HttpCache\PurgerInterface;
use ApiPlatform\HttpCache\SouinPurger;
use ApiPlatform\HttpCache\VarnishPurger;
use ApiPlatform\HttpCache\VarnishXKeyPurger;
use ApiPlatform\Laravel\Eloquent\Listener\PurgeHttpCacheListener;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpClient\HttpClient;

class ApiPlatformEventProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];

    public function register(): void
    {
        if (!interface_exists(PurgerInterface::class)) {
            return;
        }

        $this->app->singleton('api_platform.http_cache.clients_array', function (Application $app) {
            $purgerUrls = Config::get('api-platform.http_cache.invalidation.urls', []);
            $requestOptions = Config::get('api-platform.http_cache.invalidation.request_options', []);

            $clients = [];
            foreach ($purgerUrls as $url) {
                $clients[] = HttpClient::create(array_merge($requestOptions, ['base_uri' => $url]));
            }

            return $clients;
        });

        $httpClients = fn (Application $app) => $app->make('api_platform.http_cache.clients_array');

        $this->app->singleton(VarnishPurger::class, function (Application $app) use ($httpClients) {
            return new VarnishPurger($httpClients($app));
        });

        $this->app->singleton(VarnishXKeyPurger::class, function (Application $app) use ($httpClients) {
            return new VarnishXKeyPurger(
                $httpClients($app),
                Config::get('api-platform.http_cache.invalidation.max_header_length', 7500),
                Config::get('api-platform.http_cache.invalidation.xkey.glue', ' ')
            );
        });

        $this->app->singleton(SouinPurger::class, function (Application $app) use ($httpClients) {
            return new SouinPurger(
                $httpClients($app),
                Config::get('api-platform.http_cache.invalidation.max_header_length', 7500)
            );
        });

        $this->app->singleton(PurgerInterface::class, function (Application $app) {
            $purgerClass = Config::get(
                'api-platform.http_cache.invalidation.purger',
                SouinPurger::class
            );

            if (!class_exists($purgerClass)) {
                throw new \InvalidArgumentException("Purger class '{$purgerClass}' configured in api-platform.php was not found.");
            }

            return $app->make($purgerClass);
        });

        $this->app->singleton(PurgeHttpCacheListener::class, function (Application $app) {
            return new PurgeHttpCacheListener(
                $app->make(PurgerInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class)
            );
        });
    }

    public function boot(): void
    {
        if (!interface_exists(PurgerInterface::class)) {
            return;
        }

        Event::listen(RequestHandled::class, function (): void {
            Event::forget('eloquent.saved: *');
            Event::forget('eloquent.deleted: *');
            $this->app->make(PurgeHttpCacheListener::class)->postFlush();
        });
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
