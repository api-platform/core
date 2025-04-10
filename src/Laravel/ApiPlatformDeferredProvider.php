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

use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\State\Provider\DenormalizeProvider as GraphQlDenormalizeProvider;
use ApiPlatform\GraphQl\Type\ContextAwareTypeBuilderInterface;
use ApiPlatform\GraphQl\Type\FieldsBuilder;
use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\JsonApi\Filter\SparseFieldsetParameterProvider;
use ApiPlatform\Laravel\Eloquent\Extension\FilterQueryExtension;
use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Laravel\Eloquent\Filter\BooleanFilter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EndSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface as EloquentFilterInterface;
use ApiPlatform\Laravel\Eloquent\Filter\JsonApi\SortFilter;
use ApiPlatform\Laravel\Eloquent\Filter\JsonApi\SortFilterParameterProvider;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Laravel\Eloquent\Filter\StartSearchFilter;
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Resource\EloquentResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\LinksHandler;
use ApiPlatform\Laravel\Eloquent\State\LinksHandlerInterface;
use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Laravel\Metadata\CacheResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\ParameterValidationResourceMetadataCollectionFactory;
use ApiPlatform\Laravel\State\ParameterValidatorProvider;
use ApiPlatform\Laravel\State\SwaggerUiProcessor;
use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\AlternateUriResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ConcernsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\FiltersResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\FormatsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\InputOutputResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\LinkResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\OperationNameResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\PhpDocResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class ApiPlatformDeferredProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $directory = app_path();
        $classes = ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories([$directory], '(?!.*Test\.php$)');

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
            DateFilter::class,
            EndSearchFilter::class,
            EqualsFilter::class,
            OrderFilter::class,
            PartialSearchFilter::class,
            RangeFilter::class,
            StartSearchFilter::class,
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

        $this->app->singleton(ResourceMetadataCollectionFactoryInterface::class, function (Application $app) {
            /** @var ConfigRepository $config */
            $config = $app['config'];
            $formats = $config->get('api-platform.formats');

            if ($config->get('api-platform.swagger_ui.enabled', false) && !isset($formats['html'])) {
                $formats['html'] = ['text/html'];
            }

            return new CacheResourceCollectionMetadataFactory(
                new EloquentResourceCollectionMetadataFactory(
                    new ParameterValidationResourceMetadataCollectionFactory(
                        new ParameterResourceMetadataCollectionFactory(
                            $this->app->make(PropertyNameCollectionFactoryInterface::class),
                            $this->app->make(PropertyMetadataFactoryInterface::class),
                            new AlternateUriResourceMetadataCollectionFactory(
                                new FiltersResourceMetadataCollectionFactory(
                                    new FormatsResourceMetadataCollectionFactory(
                                        new InputOutputResourceMetadataCollectionFactory(
                                            new PhpDocResourceMetadataCollectionFactory(
                                                new OperationNameResourceMetadataCollectionFactory(
                                                    new LinkResourceMetadataCollectionFactory(
                                                        $app->make(LinkFactoryInterface::class),
                                                        new UriTemplateResourceMetadataCollectionFactory(
                                                            $app->make(LinkFactoryInterface::class),
                                                            $app->make(PathSegmentNameGeneratorInterface::class),
                                                            new NotExposedOperationResourceMetadataCollectionFactory(
                                                                $app->make(LinkFactoryInterface::class),
                                                                new AttributesResourceMetadataCollectionFactory(
                                                                    new ConcernsResourceMetadataCollectionFactory(
                                                                        null,
                                                                        $app->make(LoggerInterface::class),
                                                                        $config->get('api-platform.defaults', []),
                                                                        $config->get('api-platform.graphql.enabled'),
                                                                    ),
                                                                    $app->make(LoggerInterface::class),
                                                                    $config->get('api-platform.defaults', []),
                                                                    $config->get('api-platform.graphql.enabled'),
                                                                ),
                                                            )
                                                        ),
                                                        $config->get('api-platform.graphql.enabled')
                                                    )
                                                )
                                            )
                                        ),
                                        $formats,
                                        $config->get('api-platform.patch_formats'),
                                    )
                                )
                            ),
                            $app->make('filters'),
                            $app->make(CamelCaseToSnakeCaseNameConverter::class),
                            $this->app->make(LoggerInterface::class)
                        ),
                        $app->make('filters')
                    )
                ),
                true === $config->get('app.debug') ? 'array' : $config->get('api-platform.cache', 'file')
            );
        });

        if (interface_exists(FieldsBuilderEnumInterface::class)) {
            $this->registerGraphQl();
        }
    }

    private function registerGraphQl(): void
    {
        $this->app->singleton('api_platform.graphql.state_provider.parameter', function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ParameterProviderInterface::class));
            $tagged['api_platform.serializer.filter_parameter_provider'] = $app->make(SerializerFilterParameterProvider::class);

            return new ParameterProvider(
                new ParameterValidatorProvider(
                    new SecurityParameterProvider(
                        $app->make(GraphQlDenormalizeProvider::class),
                        $app->make(ResourceAccessCheckerInterface::class)
                    ),
                ),
                new ServiceLocator($tagged)
            );
        });

        $this->app->singleton(FieldsBuilderEnumInterface::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new FieldsBuilder(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(TypesContainerInterface::class),
                $app->make(ContextAwareTypeBuilderInterface::class),
                $app->make(TypeConverterInterface::class),
                $app->make(ResolverFactoryInterface::class),
                $app->make('filters'),
                $app->make(Pagination::class),
                $app->make(NameConverterInterface::class),
                $config->get('api-platform.graphql.nesting_separator') ?? '__',
                $app->make(InflectorInterface::class)
            );
        });
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
            ResourceMetadataCollectionFactoryInterface::class,
            'api_platform.graphql.state_provider.parameter',
            FieldsBuilderEnumInterface::class,
        ];
    }
}
