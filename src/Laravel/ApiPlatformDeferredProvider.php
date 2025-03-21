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

namespace ApiPlatform\Laravel;

use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\JsonApi\Filter\SparseFieldsetParameterProvider;
use ApiPlatform\Laravel\Eloquent\Extension\FilterQueryExtension;
use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Laravel\Eloquent\Filter\BooleanFilter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface as EloquentFilterInterface;
use ApiPlatform\Laravel\Eloquent\Filter\JsonApi\SortFilter;
use ApiPlatform\Laravel\Eloquent\Filter\JsonApi\SortFilterParameterProvider;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\LinksHandler;
use ApiPlatform\Laravel\Eloquent\State\LinksHandlerInterface;
use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Laravel\State\ParameterValidatorProvider;
use ApiPlatform\Laravel\State\SwaggerUiProcessor;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\Util\ReflectionClassRecursiveIterator;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Serializer\Parameter\SerializerFilterParameterProvider;
use ApiPlatform\State\CallableProcessor;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\ErrorProvider;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\State\Provider\SecurityParameterProvider;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ApiPlatformDeferredProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $directory = app_path();
        $classes = ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories([$directory]);

        $this->autoconfigure($classes, QueryExtensionInterface::class, [FilterQueryExtension::class]);
        $this->app->singleton(ItemProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(LinksHandlerInterface::class));

            return new ItemProvider(new LinksHandler($app, $app->make(ResourceMetadataCollectionFactoryInterface::class)), new ServiceLocator($tagged), $app->tagged(QueryExtensionInterface::class));
        });

        $this->app->singleton(CollectionProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(LinksHandlerInterface::class));

            return new CollectionProvider($app->make(Pagination::class), new LinksHandler($app, $app->make(ResourceMetadataCollectionFactoryInterface::class)), $app->tagged(QueryExtensionInterface::class), new ServiceLocator($tagged));
        });

        $this->app->singleton(SerializerFilterParameterProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(SerializerFilterInterface::class));

            return new SerializerFilterParameterProvider(new ServiceLocator($tagged));
        });
        $this->app->alias(SerializerFilterParameterProvider::class, 'api_platform.serializer.filter_parameter_provider');

        $this->app->singleton('filters', function (Application $app) {
            return new ServiceLocator(array_merge(
                iterator_to_array($app->tagged(SerializerFilterInterface::class)),
                iterator_to_array($app->tagged(EloquentFilterInterface::class))
            ));
        });

        $this->autoconfigure($classes, SerializerFilterInterface::class, [PropertyFilter::class]);

        $this->app->singleton(ParameterProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ParameterProviderInterface::class));
            $tagged['api_platform.serializer.filter_parameter_provider'] = $app->make(SerializerFilterParameterProvider::class);

            return new ParameterProvider(
                new ParameterValidatorProvider(
                    new SecurityParameterProvider(
                        $app->make(DeserializeProvider::class),
                        $app->make(ResourceAccessCheckerInterface::class)
                    ),
                ),
                new ServiceLocator($tagged)
            );
        });

        $this->autoconfigure($classes, ParameterProviderInterface::class, [SerializerFilterParameterProvider::class, SortFilterParameterProvider::class, SparseFieldsetParameterProvider::class]);

        $this->app->bind(FilterQueryExtension::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(EloquentFilterInterface::class));

            return new FilterQueryExtension(new ServiceLocator($tagged));
        });

        $this->autoconfigure($classes, EloquentFilterInterface::class, [
            BooleanFilter::class,
            EqualsFilter::class,
            PartialSearchFilter::class,
            DateFilter::class,
            OrderFilter::class,
            RangeFilter::class,
            SortFilter::class,
            SparseFieldset::class,
        ]);

        $this->app->singleton(CallableProcessor::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $tagged = iterator_to_array($app->tagged(ProcessorInterface::class));

            if ($config->get('api-platform.swagger_ui.enabled', false)) {
                // TODO: tag SwaggerUiProcessor instead?
                $tagged['api_platform.swagger_ui.processor'] = $app->make(SwaggerUiProcessor::class);
            }

            return new CallableProcessor(new ServiceLocator($tagged));
        });

        $this->autoconfigure($classes, ProcessorInterface::class, [RemoveProcessor::class, PersistProcessor::class]);

        $this->app->singleton(CallableProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ProviderInterface::class));

            return new CallableProvider(new ServiceLocator($tagged));
        });

        $this->autoconfigure($classes, ProviderInterface::class, [ItemProvider::class, CollectionProvider::class, ErrorProvider::class]);
    }

    /**
     * @param array<class-string, \ReflectionClass> $classes
     * @param class-string                          $interface
     * @param array<int, class-string>              $apiPlatformProviders
     */
    private function autoconfigure(array $classes, string $interface, array $apiPlatformProviders): void
    {
        $m = $apiPlatformProviders;
        foreach ($classes as $className => $refl) {
            if ($refl->implementsInterface($interface)) {
                $m[] = $className;
            }
        }

        if ($m) {
            $this->app->tag($m, $interface);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            CallableProvider::class,
            CallableProcessor::class,
            ItemProvider::class,
            CollectionProvider::class,
            SerializerFilterParameterProvider::class,
            ParameterProvider::class,
            FilterQueryExtension::class,
            'filters',
        ];
    }
}
