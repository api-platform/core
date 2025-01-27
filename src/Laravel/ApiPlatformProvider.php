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

use ApiPlatform\GraphQl\Error\ErrorHandler as GraphQlErrorHandler;
use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\Executor;
use ApiPlatform\GraphQl\ExecutorInterface;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
use ApiPlatform\GraphQl\Resolver\QueryCollectionResolverInterface;
use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use ApiPlatform\GraphQl\Resolver\ResourceFieldResolver;
use ApiPlatform\GraphQl\Serializer\Exception\ErrorNormalizer as GraphQlErrorNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\HttpExceptionNormalizer as GraphQlHttpExceptionNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\RuntimeExceptionNormalizer as GraphQlRuntimeExceptionNormalizer;
use ApiPlatform\GraphQl\Serializer\Exception\ValidationExceptionNormalizer as GraphQlValidationExceptionNormalizer;
use ApiPlatform\GraphQl\Serializer\ItemNormalizer as GraphQlItemNormalizer;
use ApiPlatform\GraphQl\Serializer\ObjectNormalizer as GraphQlObjectNormalizer;
use ApiPlatform\GraphQl\Serializer\SerializerContextBuilder as GraphQlSerializerContextBuilder;
use ApiPlatform\GraphQl\State\Processor\NormalizeProcessor;
use ApiPlatform\GraphQl\State\Provider\DenormalizeProvider as GraphQlDenormalizeProvider;
use ApiPlatform\GraphQl\State\Provider\ReadProvider as GraphQlReadProvider;
use ApiPlatform\GraphQl\State\Provider\ResolverProvider;
use ApiPlatform\GraphQl\Type\ContextAwareTypeBuilderInterface;
use ApiPlatform\GraphQl\Type\FieldsBuilder;
use ApiPlatform\GraphQl\Type\FieldsBuilderEnumInterface;
use ApiPlatform\GraphQl\Type\SchemaBuilder;
use ApiPlatform\GraphQl\Type\SchemaBuilderInterface;
use ApiPlatform\GraphQl\Type\TypeBuilder;
use ApiPlatform\GraphQl\Type\TypeConverter;
use ApiPlatform\GraphQl\Type\TypeConverterInterface;
use ApiPlatform\GraphQl\Type\TypesContainer;
use ApiPlatform\GraphQl\Type\TypesContainerInterface;
use ApiPlatform\GraphQl\Type\TypesFactory;
use ApiPlatform\GraphQl\Type\TypesFactoryInterface;
use ApiPlatform\Hal\Serializer\CollectionNormalizer as HalCollectionNormalizer;
use ApiPlatform\Hal\Serializer\EntrypointNormalizer as HalEntrypointNormalizer;
use ApiPlatform\Hal\Serializer\ItemNormalizer as HalItemNormalizer;
use ApiPlatform\Hal\Serializer\ObjectNormalizer as HalObjectNormalizer;
use ApiPlatform\Hydra\JsonSchema\SchemaFactory as HydraSchemaFactory;
use ApiPlatform\Hydra\Serializer\CollectionFiltersNormalizer as HydraCollectionFiltersNormalizer;
use ApiPlatform\Hydra\Serializer\CollectionNormalizer as HydraCollectionNormalizer;
use ApiPlatform\Hydra\Serializer\DocumentationNormalizer as HydraDocumentationNormalizer;
use ApiPlatform\Hydra\Serializer\EntrypointNormalizer as HydraEntrypointNormalizer;
use ApiPlatform\Hydra\Serializer\HydraPrefixNameConverter;
use ApiPlatform\Hydra\Serializer\PartialCollectionViewNormalizer as HydraPartialCollectionViewNormalizer;
use ApiPlatform\Hydra\State\HydraLinkProcessor;
use ApiPlatform\JsonApi\Filter\SparseFieldset;
use ApiPlatform\JsonApi\Filter\SparseFieldsetParameterProvider;
use ApiPlatform\JsonApi\JsonSchema\SchemaFactory as JsonApiSchemaFactory;
use ApiPlatform\JsonApi\Serializer\CollectionNormalizer as JsonApiCollectionNormalizer;
use ApiPlatform\JsonApi\Serializer\EntrypointNormalizer as JsonApiEntrypointNormalizer;
use ApiPlatform\JsonApi\Serializer\ErrorNormalizer as JsonApiErrorNormalizer;
use ApiPlatform\JsonApi\Serializer\ItemNormalizer as JsonApiItemNormalizer;
use ApiPlatform\JsonApi\Serializer\ObjectNormalizer as JsonApiObjectNormalizer;
use ApiPlatform\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\ContextBuilder as JsonLdContextBuilder;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\ItemNormalizer as JsonLdItemNormalizer;
use ApiPlatform\JsonLd\Serializer\ObjectNormalizer as JsonLdObjectNormalizer;
use ApiPlatform\JsonSchema\DefinitionNameFactory;
use ApiPlatform\JsonSchema\DefinitionNameFactoryInterface;
use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Laravel\ApiResource\Error;
use ApiPlatform\Laravel\ApiResource\ValidationError;
use ApiPlatform\Laravel\Controller\ApiPlatformController;
use ApiPlatform\Laravel\Controller\DocumentationController;
use ApiPlatform\Laravel\Controller\EntrypointController;
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
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentAttributePropertyMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Property\EloquentPropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\Factory\Resource\EloquentResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Eloquent\Metadata\IdentifiersExtractor as EloquentIdentifiersExtractor;
use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
use ApiPlatform\Laravel\Eloquent\Metadata\ResourceClassResolver as EloquentResourceClassResolver;
use ApiPlatform\Laravel\Eloquent\PropertyAccess\PropertyAccessor as EloquentPropertyAccessor;
use ApiPlatform\Laravel\Eloquent\Serializer\SerializerContextBuilder as EloquentSerializerContextBuilder;
use ApiPlatform\Laravel\Eloquent\Serializer\SnakeCaseToCamelCaseNameConverter;
use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\LinksHandler;
use ApiPlatform\Laravel\Eloquent\State\LinksHandlerInterface;
use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Laravel\Exception\ErrorHandler;
use ApiPlatform\Laravel\GraphQl\Controller\EntrypointController as GraphQlEntrypointController;
use ApiPlatform\Laravel\GraphQl\Controller\GraphiQlController;
use ApiPlatform\Laravel\JsonApi\State\JsonApiProvider;
use ApiPlatform\Laravel\Metadata\CachePropertyMetadataFactory;
use ApiPlatform\Laravel\Metadata\CachePropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\CacheResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\ParameterValidationResourceMetadataCollectionFactory;
use ApiPlatform\Laravel\Routing\IriConverter;
use ApiPlatform\Laravel\Routing\Router as UrlGeneratorRouter;
use ApiPlatform\Laravel\Routing\SkolemIriConverter;
use ApiPlatform\Laravel\Security\ResourceAccessChecker;
use ApiPlatform\Laravel\State\AccessCheckerProvider;
use ApiPlatform\Laravel\State\ParameterValidatorProvider;
use ApiPlatform\Laravel\State\SwaggerUiProcessor;
use ApiPlatform\Laravel\State\SwaggerUiProvider;
use ApiPlatform\Laravel\State\ValidateProvider;
use ApiPlatform\Metadata\IdentifiersExtractor;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\Property\Factory\AttributePropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\ClassLevelAttributePropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\ConcernsPropertyNameCollectionMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory;
use ApiPlatform\Metadata\Resource\Factory\AlternateUriResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceNameCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ConcernsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ConcernsResourceNameCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\FiltersResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\FormatsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\InputOutputResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\LinkResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\OperationNameResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ParameterResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\PhpDocResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\ResourceAccessCheckerInterface;
use ApiPlatform\Metadata\ResourceClassResolver;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\Serializer\Filter\FilterInterface as SerializerFilterInterface;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Serializer\ItemNormalizer;
use ApiPlatform\Serializer\JsonEncoder;
use ApiPlatform\Serializer\Mapping\Factory\ClassMetadataFactory as SerializerClassMetadataFactory;
use ApiPlatform\Serializer\Mapping\Loader\PropertyMetadataLoader;
use ApiPlatform\Serializer\Parameter\SerializerFilterParameterProvider;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\State\CallableProcessor;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\ErrorProvider;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\Processor\LinkedDataPlatformProcessor;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Provider\ContentNegotiationProvider;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\Provider\SecurityParameterProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Negotiation\Negotiator;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeZoneNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\HttpHeaderSerializer;

class ApiPlatformProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/api-platform.php', 'api-platform');

        $this->app->singleton(PropertyInfoExtractorInterface::class, function () {
            $phpDocExtractor = class_exists(DocBlockFactory::class) ? new PhpDocExtractor() : null;
            $reflectionExtractor = new ReflectionExtractor();

            return new PropertyInfoExtractor(
                [$reflectionExtractor],
                $phpDocExtractor ? [$phpDocExtractor, $reflectionExtractor] : [$reflectionExtractor],
                $phpDocExtractor ? [$phpDocExtractor] : [],
                [$reflectionExtractor],
                [$reflectionExtractor]
            );
        });

        $this->app->singleton(ModelMetadata::class);
        $this->app->bind(LoaderInterface::class, AttributeLoader::class);
        $this->app->bind(ClassMetadataFactoryInterface::class, ClassMetadataFactory::class);
        $this->app->singleton(ClassMetadataFactory::class, function (Application $app) {
            return new ClassMetadataFactory(
                new LoaderChain([
                    new PropertyMetadataLoader(
                        $app->make(PropertyNameCollectionFactoryInterface::class),
                    ),
                    new AttributeLoader(),
                ])
            );
        });

        $this->app->singleton(SerializerClassMetadataFactory::class, function (Application $app) {
            return new SerializerClassMetadataFactory($app->make(ClassMetadataFactoryInterface::class));
        });

        $this->app->bind(PathSegmentNameGeneratorInterface::class, UnderscorePathSegmentNameGenerator::class);

        $this->app->singleton(ResourceNameCollectionFactoryInterface::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $paths = $config->get('api-platform.resources') ?? [];
            $refl = new \ReflectionClass(Error::class);
            $paths[] = \dirname($refl->getFileName());

            $logger = $app->make(LoggerInterface::class);

            foreach ($paths as $i => $path) {
                if (!file_exists($path)) {
                    $logger->warning(\sprintf('We skipped reading resources in "%s" as the path does not exist. Please check the configuration at "api-platform.resources".', $path));
                    unset($paths[$i]);
                }
            }

            return new ConcernsResourceNameCollectionFactory($paths, new AttributesResourceNameCollectionFactory($paths));
        });

        $this->app->bind(ResourceClassResolverInterface::class, ResourceClassResolver::class);
        $this->app->singleton(ResourceClassResolver::class, function (Application $app) {
            return new EloquentResourceClassResolver(new ResourceClassResolver($app->make(ResourceNameCollectionFactoryInterface::class)));
        });

        $this->app->singleton(PropertyMetadataFactoryInterface::class, function (Application $app) {
            return new PropertyInfoPropertyMetadataFactory(
                $app->make(PropertyInfoExtractorInterface::class),
                new EloquentPropertyMetadataFactory(
                    $app->make(ModelMetadata::class),
                )
            );
        });

        $this->app->extend(PropertyMetadataFactoryInterface::class, function (PropertyInfoPropertyMetadataFactory $inner, Application $app) {
            /** @var ConfigRepository $config */
            $config = $app['config'];

            return new CachePropertyMetadataFactory(
                new SchemaPropertyMetadataFactory(
                    $app->make(ResourceClassResolverInterface::class),
                    new SerializerPropertyMetadataFactory(
                        $app->make(SerializerClassMetadataFactory::class),
                        new AttributePropertyMetadataFactory(
                            new EloquentAttributePropertyMetadataFactory(
                                $inner,
                            )
                        ),
                        $app->make(ResourceClassResolverInterface::class)
                    ),
                ),
                true === $config->get('app.debug') ? 'array' : $config->get('api-platform.cache', 'file')
            );
        });

        $this->app->singleton(PropertyNameCollectionFactoryInterface::class, function (Application $app) {
            /** @var ConfigRepository $config */
            $config = $app['config'];

            return new CachePropertyNameCollectionMetadataFactory(
                new ClassLevelAttributePropertyNameCollectionFactory(
                    new ConcernsPropertyNameCollectionMetadataFactory(
                        new EloquentPropertyNameCollectionMetadataFactory(
                            $app->make(ModelMetadata::class),
                            new PropertyInfoPropertyNameCollectionFactory($app->make(PropertyInfoExtractorInterface::class)),
                            $app->make(ResourceClassResolverInterface::class)
                        )
                    )
                ),
                true === $config->get('app.debug') ? 'array' : $config->get('api-platform.cache', 'file')
            );
        });

        $this->app->singleton(LinkFactoryInterface::class, function (Application $app) {
            return new LinkFactory(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(ResourceClassResolverInterface::class),
            );
        });

        // TODO: add cached metadata factories
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

        $this->app->bind(PropertyAccessorInterface::class, function () {
            return new EloquentPropertyAccessor();
        });

        $this->app->bind(NameConverterInterface::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new HydraPrefixNameConverter(new MetadataAwareNameConverter($app->make(ClassMetadataFactoryInterface::class), $app->make(SnakeCaseToCamelCaseNameConverter::class)), $defaultContext);
        });

        $this->app->bind(OperationMetadataFactoryInterface::class, OperationMetadataFactory::class);

        $this->app->tag([
            BooleanFilter::class,
            EqualsFilter::class,
            PartialSearchFilter::class,
            DateFilter::class,
            OrderFilter::class,
            RangeFilter::class,
            SortFilter::class,
            SparseFieldset::class,
        ], EloquentFilterInterface::class);

        $this->app->bind(FilterQueryExtension::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(EloquentFilterInterface::class));

            return new FilterQueryExtension(new ServiceLocator($tagged));
        });

        $this->app->tag([FilterQueryExtension::class], QueryExtensionInterface::class);

        $this->app->singleton(ItemProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(LinksHandlerInterface::class));

            return new ItemProvider(new LinksHandler($app, $app->make(ResourceMetadataCollectionFactoryInterface::class)), new ServiceLocator($tagged));
        });
        $this->app->singleton(CollectionProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(LinksHandlerInterface::class));

            return new CollectionProvider($app->make(Pagination::class), new LinksHandler($app, $app->make(ResourceMetadataCollectionFactoryInterface::class)), $app->tagged(QueryExtensionInterface::class), new ServiceLocator($tagged));
        });
        $this->app->tag([ItemProvider::class, CollectionProvider::class], ProviderInterface::class);

        $this->app->singleton(CallableProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ProviderInterface::class));

            return new CallableProvider(new ServiceLocator($tagged));
        });

        $this->app->singleton(ReadProvider::class, function (Application $app) {
            return new ReadProvider($app->make(CallableProvider::class));
        });

        $this->app->singleton(SwaggerUiProvider::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new SwaggerUiProvider($app->make(ReadProvider::class), $app->make(OpenApiFactoryInterface::class), $config->get('api-platform.swagger_ui.enabled', false));
        });

        $this->app->singleton(ValidateProvider::class, function (Application $app) {
            return new ValidateProvider($app->make(SwaggerUiProvider::class), $app);
        });

        $this->app->singleton(DeserializeProvider::class, function (Application $app) {
            return new DeserializeProvider($app->make(ValidateProvider::class), $app->make(SerializerInterface::class), $app->make(SerializerContextBuilderInterface::class));
        });

        if (class_exists(JsonApiProvider::class)) {
            $this->app->extend(DeserializeProvider::class, function (ProviderInterface $inner, Application $app) {
                return new JsonApiProvider($inner);
            });
        }

        $this->app->tag([PropertyFilter::class], SerializerFilterInterface::class);

        $this->app->singleton(SerializerFilterParameterProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(SerializerFilterInterface::class));

            return new SerializerFilterParameterProvider(new ServiceLocator($tagged));
        });
        $this->app->alias(SerializerFilterParameterProvider::class, 'api_platform.serializer.filter_parameter_provider');

        $this->app->singleton(SortFilterParameterProvider::class, function (Application $app) {
            return new SortFilterParameterProvider();
        });
        $this->app->tag([SerializerFilterParameterProvider::class, SortFilterParameterProvider::class, SparseFieldsetParameterProvider::class], ParameterProviderInterface::class);

        $this->app->singleton('filters', function (Application $app) {
            return new ServiceLocator(array_merge(
                iterator_to_array($app->tagged(SerializerFilterInterface::class)),
                iterator_to_array($app->tagged(EloquentFilterInterface::class))
            ));
        });

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

        $this->app->singleton(AccessCheckerProvider::class, function (Application $app) {
            return new AccessCheckerProvider($app->make(ParameterProvider::class), $app->make(ResourceAccessCheckerInterface::class));
        });

        $this->app->singleton(Negotiator::class, function (Application $app) {
            return new Negotiator();
        });
        $this->app->singleton(ContentNegotiationProvider::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new ContentNegotiationProvider($app->make(AccessCheckerProvider::class), $app->make(Negotiator::class), $config->get('api-platform.formats'), $config->get('api-platform.error_formats'));
        });

        $this->app->bind(ProviderInterface::class, ContentNegotiationProvider::class);

        $this->app->tag([RemoveProcessor::class, PersistProcessor::class], ProcessorInterface::class);
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

        $this->app->singleton(RespondProcessor::class, function () {
            return new RespondProcessor();
        });

        $this->app->singleton(AddLinkHeaderProcessor::class, function (Application $app) {
            return new AddLinkHeaderProcessor($app->make(RespondProcessor::class), new HttpHeaderSerializer());
        });

        $this->app->singleton(LinkedDataPlatformProcessor::class, function (Application $app) {
            return new LinkedDataPlatformProcessor(
                $app->make(AddLinkHeaderProcessor::class), // Original service
                $app->make(ResourceClassResolverInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class)
            );
        });

        $this->app->singleton(SerializeProcessor::class, function (Application $app) {
            return new SerializeProcessor($app->make(LinkedDataPlatformProcessor::class), $app->make(Serializer::class), $app->make(SerializerContextBuilderInterface::class));
        });

        $this->app->singleton(WriteProcessor::class, function (Application $app) {
            return new WriteProcessor($app->make(SerializeProcessor::class), $app->make(CallableProcessor::class));
        });

        $this->app->singleton(SerializerContextBuilder::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new SerializerContextBuilder($app->make(ResourceMetadataCollectionFactoryInterface::class), $config->get('app.debug'));
        });
        $this->app->bind(SerializerContextBuilderInterface::class, EloquentSerializerContextBuilder::class);
        $this->app->singleton(EloquentSerializerContextBuilder::class, function (Application $app) {
            return new EloquentSerializerContextBuilder(
                $app->make(SerializerContextBuilder::class),
                $app->make(PropertyNameCollectionFactoryInterface::class)
            );
        });

        $this->app->singleton(HydraLinkProcessor::class, function (Application $app) {
            return new HydraLinkProcessor($app->make(WriteProcessor::class), $app->make(UrlGeneratorInterface::class));
        });

        $this->app->bind(ProcessorInterface::class, function (Application $app) {
            $config = $app['config'];
            if ($config->has('api-platform.formats.jsonld')) {
                return $app->make(HydraLinkProcessor::class);
            }

            return $app->make(WriteProcessor::class);
        });

        $this->app->singleton(ObjectNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new ObjectNormalizer(defaultContext: $defaultContext);
        });

        $this->app->singleton(DateTimeNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new DateTimeNormalizer(defaultContext: $defaultContext);
        });

        $this->app->singleton(DateTimeZoneNormalizer::class, function () {
            return new DateTimeZoneNormalizer();
        });

        $this->app->singleton(DateIntervalNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new DateIntervalNormalizer(defaultContext: $defaultContext);
        });

        $this->app->singleton(JsonEncoder::class, function () {
            return new JsonEncoder('jsonld');
        });

        $this->app->bind(IriConverterInterface::class, IriConverter::class);
        $this->app->singleton(IriConverter::class, function (Application $app) {
            return new IriConverter($app->make(CallableProvider::class), $app->make(OperationMetadataFactoryInterface::class), $app->make(UrlGeneratorRouter::class), $app->make(IdentifiersExtractorInterface::class), $app->make(ResourceClassResolverInterface::class), $app->make(ResourceMetadataCollectionFactoryInterface::class), $app->make(SkolemIriConverter::class));
        });

        $this->app->singleton(SkolemIriConverter::class, function (Application $app) {
            return new SkolemIriConverter($app->make(UrlGeneratorRouter::class));
        });

        $this->app->bind(IdentifiersExtractorInterface::class, IdentifiersExtractor::class);
        $this->app->singleton(IdentifiersExtractor::class, function (Application $app) {
            return new EloquentIdentifiersExtractor(
                new IdentifiersExtractor(
                    $app->make(ResourceMetadataCollectionFactoryInterface::class),
                    $app->make(ResourceClassResolverInterface::class),
                    $app->make(PropertyNameCollectionFactoryInterface::class),
                    $app->make(PropertyMetadataFactoryInterface::class),
                    $app->make(PropertyAccessorInterface::class)
                )
            );
        });

        $this->app->bind(UrlGeneratorInterface::class, UrlGeneratorRouter::class);
        $this->app->singleton(UrlGeneratorRouter::class, function (Application $app) {
            $request = $app->make('request');
            // https://github.com/laravel/framework/blob/2bfb70bca53e24227a6f921f39d84ba452efd8e0/src/Illuminate/Routing/CompiledRouteCollection.php#L112
            $trimmedRequest = $request->duplicate();
            $parts = explode('?', $request->server->get('REQUEST_URI'), 2);
            $trimmedRequest->server->set(
                'REQUEST_URI',
                rtrim($parts[0], '/').(isset($parts[1]) ? '?'.$parts[1] : '')
            );

            $urlGenerator = new UrlGeneratorRouter($app->make(Router::class));
            $urlGenerator->setContext((new RequestContext())->fromRequest($trimmedRequest));

            return $urlGenerator;
        });

        $this->app->bind(ContextBuilderInterface::class, JsonLdContextBuilder::class);
        $this->app->singleton(JsonLdContextBuilder::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new JsonLdContextBuilder(
                $app->make(ResourceNameCollectionFactoryInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(UrlGeneratorInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(NameConverterInterface::class),
                $defaultContext
            );
        });

        $this->app->singleton(HydraEntrypointNormalizer::class, function (Application $app) {
            return new HydraEntrypointNormalizer($app->make(ResourceMetadataCollectionFactoryInterface::class), $app->make(IriConverterInterface::class), $app->make(UrlGeneratorInterface::class));
        });

        $this->app->singleton(ResourceAccessCheckerInterface::class, function () {
            return new ResourceAccessChecker();
        });

        $this->app->singleton(ItemNormalizer::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new ItemNormalizer(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(ClassMetadataFactoryInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(ResourceAccessCheckerInterface::class),
                $defaultContext
            );
        });

        $this->app->bind(AnonymousContextBuilderInterface::class, JsonLdContextBuilder::class);

        $this->app->singleton(JsonLdObjectNormalizer::class, function (Application $app) {
            return new JsonLdObjectNormalizer(
                $app->make(ObjectNormalizer::class),
                $app->make(IriConverterInterface::class),
                $app->make(AnonymousContextBuilderInterface::class)
            );
        });

        $this->app->singleton(HalCollectionNormalizer::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new HalCollectionNormalizer(
                $app->make(ResourceClassResolverInterface::class),
                $config->get('api-platform.pagination.page_parameter_name'),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
            );
        });

        $this->app->singleton(HalObjectNormalizer::class, function (Application $app) {
            return new HalObjectNormalizer(
                $app->make(ObjectNormalizer::class),
                $app->make(IriConverterInterface::class)
            );
        });

        $this->app->singleton(HalItemNormalizer::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new HalItemNormalizer(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(ClassMetadataFactoryInterface::class),
                $defaultContext,
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(ResourceAccessCheckerInterface::class),
            );
        });

        $this->app->singleton(Options::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new Options(
                title: $config->get('api-platform.title', ''),
                description: $config->get('api-platform.description', ''),
                version: $config->get('api-platform.version', ''),
                oAuthEnabled: $config->get('api-platform.swagger_ui.oauth.enabled', false),
                oAuthType: $config->get('api-platform.swagger_ui.oauth.type', null),
                oAuthFlow: $config->get('api-platform.swagger_ui.oauth.flow', null),
                oAuthTokenUrl: $config->get('api-platform.swagger_ui.oauth.tokenUrl', null),
                oAuthAuthorizationUrl: $config->get('api-platform.swagger_ui.oauth.authorizationUrl', null),
                oAuthRefreshUrl: $config->get('api-platform.swagger_ui.oauth.refreshUrl', null),
                oAuthScopes: $config->get('api-platform.swagger_ui.oauth.scopes', []),
                apiKeys: $config->get('api-platform.swagger_ui.apiKeys', []),
                contactName: $config->get('api-platform.swagger_ui.contact.name', ''),
                contactUrl: $config->get('api-platform.swagger_ui.contact.url', ''),
                contactEmail: $config->get('api-platform.swagger_ui.contact.email', ''),
                licenseName: $config->get('api-platform.swagger_ui.license.name', ''),
                licenseUrl: $config->get('api-platform.swagger_ui.license.url', ''),
                persistAuthorization: $config->get('api-platform.swagger_ui.persist_authorization', false),
                httpAuth: $config->get('api-platform.swagger_ui.http_auth', []),
                tags: $config->get('api-platform.openapi.tags', []),
                errorResourceClass: Error::class,
                validationErrorResourceClass: ValidationError::class
            );
        });

        $this->app->singleton(SwaggerUiProcessor::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new SwaggerUiProcessor(
                urlGenerator: $app->make(UrlGeneratorInterface::class),
                normalizer: $app->make(NormalizerInterface::class),
                openApiOptions: $app->make(Options::class),
                oauthClientId: $config->get('api-platform.swagger_ui.oauth.clientId'),
                oauthClientSecret: $config->get('api-platform.swagger_ui.oauth.clientSecret'),
                oauthPkce: $config->get('api-platform.swagger_ui.oauth.pkce', false),
            );
        });

        $this->app->singleton(DocumentationController::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new DocumentationController($app->make(ResourceNameCollectionFactoryInterface::class), $config->get('api-platform.title') ?? '', $config->get('api-platform.description') ?? '', $config->get('api-platform.version') ?? '', $app->make(OpenApiFactoryInterface::class), $app->make(ProviderInterface::class), $app->make(ProcessorInterface::class), $app->make(Negotiator::class), $config->get('api-platform.docs_formats'), $config->get('api-platform.swagger_ui.enabled', false));
        });

        $this->app->singleton(EntrypointController::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new EntrypointController($app->make(ResourceNameCollectionFactoryInterface::class), $app->make(ProviderInterface::class), $app->make(ProcessorInterface::class), $config->get('api-platform.docs_formats'));
        });

        $this->app->singleton(Pagination::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new Pagination($config->get('api-platform.pagination'), []);
        });

        $this->app->singleton(PaginationOptions::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $defaults = $config->get('api-platform.defaults');
            $pagination = $config->get('api-platform.pagination');

            return new PaginationOptions(
                $defaults['pagination_enabled'],
                $pagination['page_parameter_name'],
                $defaults['pagination_client_items_per_page'],
                $pagination['items_per_page_parameter_name'],
                $defaults['pagination_client_enabled'],
                $pagination['enabled_parameter_name'],
                $defaults['pagination_items_per_page'],
                $defaults['pagination_maximum_items_per_page'],
                $defaults['pagination_partial'],
                $defaults['pagination_client_partial'],
                $pagination['partial_parameter_name'],
            );
        });

        $this->app->bind(OpenApiFactoryInterface::class, OpenApiFactory::class);
        $this->app->singleton(OpenApiFactory::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new OpenApiFactory(
                $app->make(ResourceNameCollectionFactoryInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(SchemaFactoryInterface::class),
                null,
                $config->get('api-platform.formats'),
                $app->make(Options::class),
                $app->make(PaginationOptions::class),
                null,
                $config->get('api-platform.error_formats'),
                // ?RouterInterface $router = null
            );
        });

        $this->app->bind(DefinitionNameFactoryInterface::class, DefinitionNameFactory::class);
        $this->app->singleton(DefinitionNameFactory::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new DefinitionNameFactory($config->get('api-platform.formats'));
        });

        $this->app->singleton(SchemaFactory::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new SchemaFactory(
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $config->get('api-platform.formats'),
                $app->make(DefinitionNameFactoryInterface::class),
            );
        });
        $this->app->singleton(JsonApiSchemaFactory::class, function (Application $app) {
            return new JsonApiSchemaFactory(
                $app->make(SchemaFactory::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(DefinitionNameFactoryInterface::class),
            );
        });
        $this->app->singleton(HydraSchemaFactory::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new HydraSchemaFactory(
                $app->make(JsonApiSchemaFactory::class),
                $defaultContext
            );
        });

        $this->app->bind(SchemaFactoryInterface::class, HydraSchemaFactory::class);

        $this->app->singleton(OpenApiNormalizer::class, function (Application $app) {
            return new OpenApiNormalizer($app->make(ObjectNormalizer::class));
        });

        $this->app->singleton(HydraDocumentationNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new HydraDocumentationNormalizer(
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(UrlGeneratorInterface::class),
                $app->make(NameConverterInterface::class),
                $defaultContext
            );
        });

        $this->app->singleton(HydraPartialCollectionViewNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new HydraPartialCollectionViewNormalizer(
                new HydraCollectionFiltersNormalizer(
                    new HydraCollectionNormalizer(
                        $app->make(ContextBuilderInterface::class),
                        $app->make(ResourceClassResolverInterface::class),
                        $app->make(IriConverterInterface::class),
                        $app->make(ResourceMetadataCollectionFactoryInterface::class),
                        $defaultContext
                    ),
                    $app->make(ResourceMetadataCollectionFactoryInterface::class),
                    $app->make(ResourceClassResolverInterface::class),
                    null, // filterLocator, we use only Parameters with Laravel and we don't need to call filters there
                    $defaultContext
                ),
                'page',
                'pagination',
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $config->get('api-platform.url_generation_strategy', UrlGeneratorInterface::ABS_PATH),
                $defaultContext,
            );
        });

        $this->app->singleton(ReservedAttributeNameConverter::class, function (Application $app) {
            return new ReservedAttributeNameConverter($app->make(NameConverterInterface::class));
        });

        if (interface_exists(FieldsBuilderEnumInterface::class)) {
            $this->registerGraphQl($this->app);
        }

        $this->app->singleton(JsonApiEntrypointNormalizer::class, function (Application $app) {
            return new JsonApiEntrypointNormalizer(
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(UrlGeneratorInterface::class),
            );
        });

        $this->app->singleton(JsonApiCollectionNormalizer::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new JsonApiCollectionNormalizer(
                $app->make(ResourceClassResolverInterface::class),
                $config->get('api-platform.pagination.page_parameter_name'),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
            );
        });

        $this->app->singleton(JsonApiItemNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new JsonApiItemNormalizer(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(ClassMetadataFactoryInterface::class),
                $defaultContext,
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(ResourceAccessCheckerInterface::class),
                null
                // $app->make(TagCollectorInterface::class),
            );
        });

        $this->app->singleton(JsonApiErrorNormalizer::class, function (Application $app) {
            return new JsonApiErrorNormalizer(
                $app->make(JsonApiItemNormalizer::class),
            );
        });

        $this->app->singleton(JsonApiObjectNormalizer::class, function (Application $app) {
            return new JsonApiObjectNormalizer(
                $app->make(ObjectNormalizer::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
            );
        });

        $this->app->singleton('api_platform_normalizer_list', function (Application $app) {
            $list = new \SplPriorityQueue();
            $list->insert($app->make(HydraEntrypointNormalizer::class), -800);
            $list->insert($app->make(HydraPartialCollectionViewNormalizer::class), -800);
            $list->insert($app->make(HalCollectionNormalizer::class), -800);
            $list->insert($app->make(HalEntrypointNormalizer::class), -985);
            $list->insert($app->make(HalObjectNormalizer::class), -995);
            $list->insert($app->make(HalItemNormalizer::class), -890);
            $list->insert($app->make(JsonLdItemNormalizer::class), -890);
            $list->insert($app->make(JsonLdObjectNormalizer::class), -995);
            $list->insert($app->make(ArrayDenormalizer::class), -990);
            $list->insert($app->make(DateTimeZoneNormalizer::class), -915);
            $list->insert($app->make(DateIntervalNormalizer::class), -915);
            $list->insert($app->make(DateTimeNormalizer::class), -910);
            $list->insert($app->make(BackedEnumNormalizer::class), -910);
            $list->insert($app->make(ObjectNormalizer::class), -1000);
            $list->insert($app->make(ItemNormalizer::class), -895);
            $list->insert($app->make(OpenApiNormalizer::class), -780);
            $list->insert($app->make(HydraDocumentationNormalizer::class), -790);

            $list->insert($app->make(JsonApiEntrypointNormalizer::class), -800);
            $list->insert($app->make(JsonApiCollectionNormalizer::class), -985);
            $list->insert($app->make(JsonApiItemNormalizer::class), -890);
            $list->insert($app->make(JsonApiErrorNormalizer::class), -790);
            $list->insert($app->make(JsonApiObjectNormalizer::class), -995);

            if (interface_exists(FieldsBuilderEnumInterface::class)) {
                $list->insert($app->make(GraphQlItemNormalizer::class), -890);
                $list->insert($app->make(GraphQlObjectNormalizer::class), -995);
                $list->insert($app->make(GraphQlErrorNormalizer::class), -790);
                $list->insert($app->make(GraphQlValidationExceptionNormalizer::class), -780);
                $list->insert($app->make(GraphQlHttpExceptionNormalizer::class), -780);
                $list->insert($app->make(GraphQlRuntimeExceptionNormalizer::class), -780);
            }

            return $list;
        });

        $this->app->bind(SerializerInterface::class, Serializer::class);
        $this->app->bind(NormalizerInterface::class, Serializer::class);
        $this->app->singleton(Serializer::class, function (Application $app) {
            // TODO: unused + implement hal/jsonapi ?
            // $list->insert($dataUriNormalizer, -920);
            // $list->insert($unwrappingDenormalizer, 1000);
            // $list->insert($jsonserializableNormalizer, -900);
            // $list->insert($uuidDenormalizer, -895); //Todo ramsey uuid support ?

            return new Serializer(
                iterator_to_array($app->make('api_platform_normalizer_list')),
                [
                    new JsonEncoder('json'),
                    $app->make(JsonEncoder::class),
                    new JsonEncoder('jsonopenapi'),
                    new JsonEncoder('jsonapi'),
                    new JsonEncoder('jsonhal'),
                    new CsvEncoder(),
                ]
            );
        });

        $this->app->singleton(JsonLdItemNormalizer::class, function (Application $app) {
            $config = $app['config'];
            $defaultContext = $config->get('api-platform.serializer', []);

            return new JsonLdItemNormalizer(
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(ContextBuilderInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(ClassMetadataFactoryInterface::class),
                $defaultContext,
                $app->make(ResourceAccessCheckerInterface::class)
            );
        });

        $this->app->singleton(
            ExceptionHandlerInterface::class,
            function (Application $app) {
                /** @var ConfigRepository */
                $config = $app['config'];

                return new ErrorHandler(
                    $app,
                    $app->make(ResourceMetadataCollectionFactoryInterface::class),
                    $app->make(ApiPlatformController::class),
                    $app->make(IdentifiersExtractorInterface::class),
                    $app->make(ResourceClassResolverInterface::class),
                    $app->make(Negotiator::class),
                    $config->get('api-platform.exception_to_status'),
                    $config->get('app.debug')
                );
            }
        );

        $this->app->singleton(InflectorInterface::class, function (Application $app) {
            return new Inflector();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\InstallCommand::class,
                Console\Maker\MakeStateProcessorCommand::class,
                Console\Maker\MakeStateProviderCommand::class,
            ]);
        }
    }

    private function registerGraphQl(Application $app): void
    {
        $this->app->singleton(GraphQlItemNormalizer::class, function (Application $app) {
            return new GraphQlItemNormalizer(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(IdentifiersExtractorInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(SerializerClassMetadataFactory::class),
                null,
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(ResourceAccessCheckerInterface::class)
            );
        });

        $this->app->singleton(GraphQlObjectNormalizer::class, function (Application $app) {
            return new GraphQlObjectNormalizer(
                $app->make(ObjectNormalizer::class),
                $app->make(IriConverterInterface::class),
                $app->make(IdentifiersExtractorInterface::class),
            );
        });

        $this->app->singleton(GraphQlErrorNormalizer::class, function () {
            return new GraphQlErrorNormalizer();
        });

        $this->app->singleton(GraphQlValidationExceptionNormalizer::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new GraphQlValidationExceptionNormalizer($config->get('api-platform.exception_to_status'));
        });

        $this->app->singleton(GraphQlHttpExceptionNormalizer::class, function () {
            return new GraphQlHttpExceptionNormalizer();
        });

        $this->app->singleton(GraphQlRuntimeExceptionNormalizer::class, function () {
            return new GraphQlHttpExceptionNormalizer();
        });

        $app->singleton('api_platform.graphql.type_locator', function (Application $app) {
            $tagged = iterator_to_array($app->tagged('api_platform.graphql.type'));
            $services = [];
            foreach ($tagged as $service) {
                $services[$service->name] = $service;
            }

            return new ServiceLocator($services);
        });

        $app->singleton(TypesFactoryInterface::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged('api_platform.graphql.type'));

            return new TypesFactory($app->make('api_platform.graphql.type_locator'), array_column($tagged, 'name'));
        });
        $app->singleton(TypesContainerInterface::class, function () {
            return new TypesContainer();
        });

        $app->singleton(ResourceFieldResolver::class, function (Application $app) {
            return new ResourceFieldResolver($app->make(IriConverterInterface::class));
        });

        $app->singleton(ContextAwareTypeBuilderInterface::class, function (Application $app) {
            return new TypeBuilder(
                $app->make(TypesContainerInterface::class),
                $app->make(ResourceFieldResolver::class),
                null,
                $app->make(Pagination::class)
            );
        });

        $app->singleton(TypeConverterInterface::class, function (Application $app) {
            return new TypeConverter(
                $app->make(ContextAwareTypeBuilderInterface::class),
                $app->make(TypesContainerInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
            );
        });

        $app->singleton(GraphQlSerializerContextBuilder::class, function (Application $app) {
            return new GraphQlSerializerContextBuilder($app->make(NameConverterInterface::class));
        });

        $app->singleton(GraphQlReadProvider::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new GraphQlReadProvider(
                $this->app->make(CallableProvider::class),
                $app->make(IriConverterInterface::class),
                $app->make(GraphQlSerializerContextBuilder::class),
                $config->get('api-platform.graphql.nesting_separator') ?? '__'
            );
        });
        $app->alias(GraphQlReadProvider::class, 'api_platform.graphql.state_provider.read');

        $app->singleton(ErrorProvider::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new ErrorProvider(
                $config->get('app.debug'),
                $app->make(ResourceClassResolver::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
            );
        });
        $app->tag([ErrorProvider::class], ProviderInterface::class);

        $app->singleton(ResolverProvider::class, function (Application $app) {
            $resolvers = iterator_to_array($app->tagged('api_platform.graphql.resolver'));
            $taggedItemResolvers = iterator_to_array($app->tagged(QueryItemResolverInterface::class));
            $taggedCollectionResolvers = iterator_to_array($app->tagged(QueryCollectionResolverInterface::class));

            return new ResolverProvider(
                $app->make(GraphQlReadProvider::class),
                new ServiceLocator([...$resolvers, ...$taggedItemResolvers, ...$taggedCollectionResolvers]),
            );
        });

        $app->alias(ResolverProvider::class, 'api_platform.graphql.state_provider.resolver');

        $app->singleton(GraphQlDenormalizeProvider::class, function (Application $app) {
            return new GraphQlDenormalizeProvider(
                $this->app->make(ResolverProvider::class),
                $app->make(SerializerInterface::class),
                $app->make(GraphQlSerializerContextBuilder::class)
            );
        });

        $app->alias(GraphQlDenormalizeProvider::class, 'api_platform.graphql.state_provider.denormalize');

        $app->singleton('api_platform.graphql.state_provider.parameter', function (Application $app) {
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

        $app->singleton('api_platform.graphql.state_provider.access_checker', function (Application $app) {
            return new AccessCheckerProvider($app->make('api_platform.graphql.state_provider.parameter'), $app->make(ResourceAccessCheckerInterface::class));
        });

        $app->singleton(NormalizeProcessor::class, function (Application $app) {
            return new NormalizeProcessor(
                $app->make(SerializerInterface::class),
                $app->make(GraphQlSerializerContextBuilder::class),
                $app->make(Pagination::class)
            );
        });
        $app->alias(NormalizeProcessor::class, 'api_platform.graphql.state_processor.normalize');

        $app->singleton('api_platform.graphql.state_processor', function (Application $app) {
            return new WriteProcessor(
                $app->make('api_platform.graphql.state_processor.normalize'),
                $app->make(CallableProcessor::class),
            );
        });

        $app->singleton(ResolverFactoryInterface::class, function (Application $app) {
            return new ResolverFactory(
                $app->make('api_platform.graphql.state_provider.access_checker'),
                $app->make('api_platform.graphql.state_processor')
            );
        });

        $app->singleton(FieldsBuilderEnumInterface::class, function (Application $app) {
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

        $app->singleton(SchemaBuilderInterface::class, function (Application $app) {
            return new SchemaBuilder($app->make(ResourceNameCollectionFactoryInterface::class), $app->make(ResourceMetadataCollectionFactoryInterface::class), $app->make(TypesFactoryInterface::class), $app->make(TypesContainerInterface::class), $app->make(FieldsBuilderEnumInterface::class));
        });

        $app->singleton(ErrorHandlerInterface::class, function () {
            return new GraphQlErrorHandler();
        });

        $app->singleton(ExecutorInterface::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new Executor($config->get('api-platform.graphql.introspection.enabled') ?? false, $config->get('api-platform.graphql.max_query_complexity') ?? 500, $config->get('api-platform.graphql.max_query_depth') ?? 200);
        });

        $app->singleton(GraphiQlController::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $prefix = $config->get('api-platform.defaults.route_prefix') ?? '';

            return new GraphiQlController($prefix);
        });

        $app->singleton(GraphQlEntrypointController::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new GraphQlEntrypointController(
                $app->make(SchemaBuilderInterface::class),
                $app->make(ExecutorInterface::class),
                $app->make(GraphiQlController::class),
                $app->make(SerializerInterface::class),
                $app->make(ErrorHandlerInterface::class),
                debug: $config->get('app.debug'),
                negotiator: $app->make(Negotiator::class),
                formats: $config->get('api-platform.formats')
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/api-platform.php' => $this->app->configPath('api-platform.php'),
            ], 'api-platform-config');

            $this->publishes([
                __DIR__.'/public' => $this->app->publicPath('vendor/api-platform'),
            ], ['api-platform-assets', 'public']);
        }

        $this->loadViewsFrom(__DIR__.'/resources/views', 'api-platform');

        $config = $this->app['config'];

        if ($config->get('api-platform.graphql.enabled')) {
            $fieldsBuilder = $this->app->make(FieldsBuilderEnumInterface::class);
            $typeBuilder = $this->app->make(ContextAwareTypeBuilderInterface::class);
            $typeBuilder->setFieldsBuilderLocator(new ServiceLocator(['api_platform.graphql.fields_builder' => $fieldsBuilder]));
        }

        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
    }
}
