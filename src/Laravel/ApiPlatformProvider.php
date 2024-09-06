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

use ApiPlatform\Documentation\Action\DocumentationAction;
use ApiPlatform\Documentation\Action\EntrypointAction;
use ApiPlatform\GraphQl\Error\ErrorHandler as GraphQlErrorHandler;
use ApiPlatform\GraphQl\Error\ErrorHandlerInterface;
use ApiPlatform\GraphQl\Executor;
use ApiPlatform\GraphQl\ExecutorInterface;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactory;
use ApiPlatform\GraphQl\Resolver\Factory\ResolverFactoryInterface;
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
use ApiPlatform\Hydra\JsonSchema\SchemaFactory as HydraSchemaFactory;
use ApiPlatform\Hydra\Serializer\CollectionNormalizer as HydraCollectionNormalizer;
use ApiPlatform\Hydra\Serializer\DocumentationNormalizer as HydraDocumentationNormalizer;
use ApiPlatform\Hydra\Serializer\EntrypointNormalizer as HydraEntrypointNormalizer;
use ApiPlatform\Hydra\Serializer\PartialCollectionViewNormalizer as HydraPartialCollectionViewNormalizer;
use ApiPlatform\Hydra\State\HydraLinkProcessor;
use ApiPlatform\JsonApi\JsonSchema\SchemaFactory as JsonApiSchemaFactory;
use ApiPlatform\JsonApi\Serializer\CollectionNormalizer as JsonApiCollectionNormalizer;
use ApiPlatform\JsonApi\Serializer\EntrypointNormalizer as JsonApiEntrypointNormalizer;
use ApiPlatform\JsonApi\Serializer\ItemNormalizer as JsonApiItemNormalizer;
use ApiPlatform\JsonApi\Serializer\ObjectNormalizer as JsonApiObjectNormalizer;
use ApiPlatform\JsonApi\Serializer\ReservedAttributeNameConverter;
use ApiPlatform\JsonApi\State\JsonApiProvider;
use ApiPlatform\JsonLd\Action\ContextAction;
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
use ApiPlatform\Laravel\Controller\ApiPlatformController;
use ApiPlatform\Laravel\Eloquent\Extension\FilterQueryExtension;
use ApiPlatform\Laravel\Eloquent\Extension\QueryExtensionInterface;
use ApiPlatform\Laravel\Eloquent\Filter\AfterDateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\BeforeDateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EndSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\FilterInterface as EloquentFilterInterface;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Laravel\Eloquent\Filter\StartSearchFilter;
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
use ApiPlatform\Laravel\Metadata\CachePropertyMetadataFactory;
use ApiPlatform\Laravel\Metadata\CachePropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\CacheResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Routing\IriConverter;
use ApiPlatform\Laravel\Routing\Router as UrlGeneratorRouter;
use ApiPlatform\Laravel\Routing\SkolemIriConverter;
use ApiPlatform\Laravel\Security\ResourceAccessChecker;
use ApiPlatform\Laravel\State\AccessCheckerProvider;
use ApiPlatform\Laravel\State\SwaggerUiProcessor;
use ApiPlatform\Laravel\State\SwaggerUiProvider;
use ApiPlatform\Laravel\State\ValidateProvider;
use ApiPlatform\Metadata\Exception\NotExposedHttpException;
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
use ApiPlatform\Serializer\Parameter\SerializerFilterParameterProvider;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\State\CallableProcessor;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\ParameterProviderInterface;
use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Provider\ContentNegotiationProvider;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ParameterProvider;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
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
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
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
        $defaultContext = [];

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

        $this->app->bind(LoaderInterface::class, AttributeLoader::class);
        $this->app->bind(ClassMetadataFactoryInterface::class, ClassMetadataFactory::class);
        $this->app->singleton(ClassMetadataFactory::class, function () {
            return new ClassMetadataFactory(new AttributeLoader());
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
            /** @var ConfigRepository */
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
                true === $config->get('app.debug') ? 'array' : 'file'
            );
        });

        $this->app->singleton(PropertyNameCollectionFactoryInterface::class, function (Application $app) {
            /** @var ConfigRepository */
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
                true === $config->get('app.debug') ? 'array' : 'file'
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
            /** @var ConfigRepository */
            $config = $app['config'];

            return new CacheResourceCollectionMetadataFactory(
                new EloquentResourceCollectionMetadataFactory(
                    new ParameterResourceMetadataCollectionFactory(
                        $this->app->make(PropertyNameCollectionFactoryInterface::class),
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
                                                            new ConcernsResourceMetadataCollectionFactory(
                                                                new AttributesResourceMetadataCollectionFactory(
                                                                    null,
                                                                    $app->make(LoggerInterface::class),
                                                                    [
                                                                        'routePrefix' => $config->get('api-platform.routes.prefix') ?? '/',
                                                                    ],
                                                                    $config->get('api-platform.graphql.enabled'),
                                                                ),
                                                                $app->make(LoggerInterface::class),
                                                                [
                                                                    'routePrefix' => $config->get('api-platform.routes.prefix') ?? '/',
                                                                ],
                                                                $config->get('api-platform.graphql.enabled'),
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    ),
                                    $config->get('api-platform.formats'),
                                    $config->get('api-platform.patch_formats'),
                                )
                            )
                        ),
                        $app->make('filters'),
                        $app->make(CamelCaseToSnakeCaseNameConverter::class)
                    )
                ),
                true === $config->get('app.debug') ? 'array' : 'file'
            );
        });

        $this->app->bind(PropertyAccessorInterface::class, function () {
            return new EloquentPropertyAccessor();
        });

        $this->app->bind(NameConverterInterface::class, function (Application $app) {
            return new MetadataAwareNameConverter($app->make(ClassMetadataFactoryInterface::class), $app->make(SnakeCaseToCamelCaseNameConverter::class));
        });

        $this->app->bind(OperationMetadataFactoryInterface::class, OperationMetadataFactory::class);

        $this->app->tag([EqualsFilter::class, PartialSearchFilter::class, StartSearchFilter::class, EndSearchFilter::class, RangeFilter::class, DateFilter::class, BeforeDateFilter::class, AfterDateFilter::class, OrderFilter::class], EloquentFilterInterface::class);

        $this->app->bind(FilterQueryExtension::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(EloquentFilterInterface::class));

            return new FilterQueryExtension(new ServiceLocator($tagged));
        });

        $this->app->tag([FilterQueryExtension::class], QueryExtensionInterface::class);

        $this->app->singleton(ItemProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(LinksHandlerInterface::class));

            return new ItemProvider(new LinksHandler($app), new ServiceLocator($tagged));
        });
        $this->app->singleton(CollectionProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(LinksHandlerInterface::class));

            return new CollectionProvider($app->make(Pagination::class), new LinksHandler($app), $app->tagged(QueryExtensionInterface::class), new ServiceLocator($tagged));
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
            return new SwaggerUiProvider($app->make(ReadProvider::class), $app->make(OpenApiFactoryInterface::class));
        });

        $this->app->singleton(ValidateProvider::class, function (Application $app) {
            return new ValidateProvider($app->make(SwaggerUiProvider::class), $app);
        });

        $this->app->singleton(JsonApiProvider::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new JsonApiProvider($app->make(ValidateProvider::class), $config->get('api-platform.collection.order.parameter_name'));
        });

        $this->app->singleton(DeserializeProvider::class, function (Application $app) {
            return new DeserializeProvider($app->make(JsonApiProvider::class), $app->make(SerializerInterface::class), $app->make(SerializerContextBuilderInterface::class));
        });

        $this->app->tag([PropertyFilter::class], SerializerFilterInterface::class);

        $this->app->singleton(SerializerFilterParameterProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(SerializerFilterInterface::class));

            return new SerializerFilterParameterProvider(new ServiceLocator($tagged));
        });

        $this->app->tag([SerializerFilterParameterProvider::class], ParameterProviderInterface::class);

        $this->app->singleton('filters', function (Application $app) {
            return new ServiceLocator(array_merge(
                iterator_to_array($app->tagged(SerializerFilterInterface::class)),
                iterator_to_array($app->tagged(EloquentFilterInterface::class))
            ));
        });

        $this->app->singleton(ParameterProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ParameterProviderInterface::class));

            return new ParameterProvider($app->make(DeserializeProvider::class), new ServiceLocator($tagged));
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
            $tagged = iterator_to_array($app->tagged(ProcessorInterface::class));
            // TODO: tag SwaggerUiProcessor instead?
            $tagged['api_platform.swagger_ui.processor'] = $app->make(SwaggerUiProcessor::class);

            return new CallableProcessor(new ServiceLocator($tagged));
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

        $this->app->singleton(SerializeProcessor::class, function (Application $app) {
            return new SerializeProcessor($app->make(RespondProcessor::class), $app->make(Serializer::class), $app->make(SerializerContextBuilderInterface::class));
        });

        $this->app->singleton(HydraLinkProcessor::class, function (Application $app) {
            return new HydraLinkProcessor($app->make(WriteProcessor::class), $app->make(UrlGeneratorInterface::class));
        });

        $this->app->singleton(RespondProcessor::class, function () {
            return new AddLinkHeaderProcessor(new RespondProcessor(), new HttpHeaderSerializer());
        });

        $this->app->bind(ProcessorInterface::class, HydraLinkProcessor::class);

        $this->app->singleton(ObjectNormalizer::class, function () {
            return new ObjectNormalizer();
        });

        $this->app->singleton(DateTimeNormalizer::class, function () {
            return new DateTimeNormalizer();
        });

        $this->app->singleton(DateTimeZoneNormalizer::class, function () {
            return new DateTimeZoneNormalizer();
        });

        $this->app->singleton(DateIntervalNormalizer::class, function () {
            return new DateIntervalNormalizer();
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
            return new JsonLdContextBuilder(
                $app->make(ResourceNameCollectionFactoryInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(UrlGeneratorInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(NameConverterInterface::class)
            );
        });

        $this->app->singleton(HydraEntrypointNormalizer::class, function (Application $app) {
            return new HydraEntrypointNormalizer($app->make(ResourceMetadataCollectionFactoryInterface::class), $app->make(IriConverterInterface::class), $app->make(UrlGeneratorInterface::class));
        });

        $this->app->singleton(ResourceAccessCheckerInterface::class, function () {
            return new ResourceAccessChecker();
        });

        $this->app->singleton(ItemNormalizer::class, function (Application $app) use ($defaultContext) {
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

        $this->app->singleton(Options::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new Options(title: $config->get('api-platform.title') ?? '');
        });

        $this->app->singleton(DocumentationAction::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new DocumentationAction($app->make(ResourceNameCollectionFactoryInterface::class), $config->get('api-platform.title') ?? '', $config->get('api-platform.description') ?? '', $config->get('api-platform.version') ?? '', $app->make(OpenApiFactoryInterface::class), $app->make(ProviderInterface::class), $app->make(ProcessorInterface::class), $app->make(Negotiator::class), $config->get('api-platform.docs_formats'));
        });

        $this->app->singleton(EntrypointAction::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new EntrypointAction($app->make(ResourceNameCollectionFactoryInterface::class), $app->make(ProviderInterface::class), $app->make(ProcessorInterface::class), $config->get('api-platform.docs_formats'));
        });

        $this->app->singleton(Pagination::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];

            return new Pagination($config->get('api-platform.collection.pagination'), []);
        });

        $this->app->singleton(PaginationOptions::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $pagination = $config->get('api-platform.collection.pagination');

            return new PaginationOptions(
                $pagination['enabled'],
                $pagination['page_parameter_name'],
                $pagination['client_items_per_page'],
                $pagination['items_per_page_parameter_name'],
                $pagination['client_enabled'],
                $pagination['enabled_parameter_name'],
                $pagination['items_per_page'],
                $pagination['maximum_items_per_page'],
                $pagination['partial'],
                $pagination['client_partial'],
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
                null, // ?Options $openApiOptions = null,
                $app->make(PaginationOptions::class), // ?PaginationOptions $paginationOptions = null,
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
            return new HydraSchemaFactory(
                $app->make(JsonApiSchemaFactory::class),
            );
        });

        $this->app->bind(SchemaFactoryInterface::class, HydraSchemaFactory::class);

        $this->app->singleton(OpenApiNormalizer::class, function (Application $app) {
            return new OpenApiNormalizer($app->make(ObjectNormalizer::class));
        });

        $this->app->singleton(HydraDocumentationNormalizer::class, function (Application $app) {
            return new HydraDocumentationNormalizer(
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(UrlGeneratorInterface::class),
                $app->make(NameConverterInterface::class)
            );
        });

        $this->app->singleton(HydraPartialCollectionViewNormalizer::class, function (Application $app) use ($defaultContext) {
            return new HydraPartialCollectionViewNormalizer(
                new HydraCollectionNormalizer(
                    $app->make(ContextBuilderInterface::class),
                    $app->make(ResourceClassResolverInterface::class),
                    $app->make(IriConverterInterface::class),
                    $app->make(ResourceMetadataCollectionFactoryInterface::class),
                    $defaultContext
                ),
                'page',
                'pagination',
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyAccessorInterface::class),
            );
        });

        $this->app->singleton(ReservedAttributeNameConverter::class, function (Application $app) {
            return new ReservedAttributeNameConverter($app->make(NameConverterInterface::class));
        });

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
                $config->get('api-platform.collection.pagination.page_parameter_name'),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
            );
        });

        $this->app->singleton(JsonApiItemNormalizer::class, function (Application $app) use ($defaultContext) {
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

        $this->app->singleton(JsonApiObjectNormalizer::class, function (Application $app) {
            return new JsonApiObjectNormalizer(
                $app->make(ObjectNormalizer::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
            );
        });

        if ($this->app['config']->get('api-platform.graphql.enabled')) {
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
        }

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

        $this->app->bind(SerializerInterface::class, Serializer::class);
        $this->app->bind(NormalizerInterface::class, Serializer::class);
        $this->app->singleton(Serializer::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $list = new \SplPriorityQueue();
            $list->insert($app->make(HydraEntrypointNormalizer::class), -800);
            $list->insert($app->make(HydraPartialCollectionViewNormalizer::class), -800);
            $list->insert($app->make(JsonLdItemNormalizer::class), -890);
            $list->insert($app->make(JsonLdObjectNormalizer::class), -995);
            $list->insert($app->make(ArrayDenormalizer::class), -990);
            $list->insert($app->make(DateTimeZoneNormalizer::class), -915);
            $list->insert($app->make(DateIntervalNormalizer::class), -915);
            $list->insert($app->make(DateTimeNormalizer::class), -910);
            $list->insert($app->make(ObjectNormalizer::class), -1000);
            $list->insert($app->make(ItemNormalizer::class), -895);
            $list->insert($app->make(OpenApiNormalizer::class), -780);
            $list->insert($app->make(HydraDocumentationNormalizer::class), -790);

            $list->insert($app->make(JsonApiEntrypointNormalizer::class), -800);
            $list->insert($app->make(JsonApiCollectionNormalizer::class), -985);
            $list->insert($app->make(JsonApiItemNormalizer::class), -890);
            $list->insert($app->make(JsonApiObjectNormalizer::class), -995);

            if ($config->get('api-platform.graphql.enabled')) {
                $list->insert($app->make(GraphQlItemNormalizer::class), -890);
                $list->insert($app->make(GraphQlObjectNormalizer::class), -995);
                $list->insert($app->make(GraphQlErrorNormalizer::class), -790);
                $list->insert($app->make(GraphQlValidationExceptionNormalizer::class), -780);
                $list->insert($app->make(GraphQlHttpExceptionNormalizer::class), -780);
                $list->insert($app->make(GraphQlRuntimeExceptionNormalizer::class), -780);
            }

            // TODO: unused + implement hal/jsonapi ?
            // $list->insert($dataUriNormalizer, -920);
            // $list->insert($unwrappingDenormalizer, 1000);
            // $list->insert($halItemNormalizer, -890);
            // $list->insert($halEntrypointNormalizer, -800);
            // $list->insert($halCollectionNormalizer, -985);
            // $list->insert($halObjectNormalizer, -995);
            // $list->insert($jsonserializableNormalizer, -900);
            // $list->insert($uuidDenormalizer, -895); //Todo ramsey uuid support ?

            return new Serializer(iterator_to_array($list), [new JsonEncoder('json'), $app->make(JsonEncoder::class), new JsonEncoder('jsonopenapi'), new JsonEncoder('jsonapi')]);
        });

        $this->app->singleton(JsonLdItemNormalizer::class, function (Application $app) use ($defaultContext) {
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

        if ($this->app['config']->get('api-platform.graphql.enabled')) {
            $this->registerGraphQl($this->app);
        }
    }

    private function registerGraphQl(Application $app): void
    {
        $app->singleton('api_platform.graphql.type_locator', function (Application $app) {
            $tagged = iterator_to_array($app->tagged('api_platform.graphql.type'));

            return new ServiceLocator($tagged);
        });

        $app->singleton(TypesFactoryInterface::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged('api_platform.graphql.type'));

            return new TypesFactory($app->make('api_platform.graphql.type_locator'), array_keys($tagged));
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

        $app->singleton('api_platform.graphql.state_provider', function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $tagged = iterator_to_array($app->tagged(ParameterProviderInterface::class));
            $resolvers = iterator_to_array($app->tagged('api_platform.graphql.resolver'));

            return new GraphQlReadProvider(
                new GraphQlDenormalizeProvider(
                    new ResolverProvider(
                        new ParameterProvider(
                            $app->make(CallableProvider::class),
                            new ServiceLocator($tagged)
                        ),
                        new ServiceLocator($resolvers),
                    ),
                    $app->make(SerializerInterface::class),
                    $app->make(GraphQlSerializerContextBuilder::class)
                ),
                $app->make(IriConverterInterface::class),
                $app->make(GraphQlSerializerContextBuilder::class),
                $config->get('api-platform.graphql.nesting_separator') ?? '__'
            );
        });

        $app->singleton('api_platform.graphql.state_processor', function (Application $app) {
            return new WriteProcessor(
                new NormalizeProcessor(
                    $app->make(SerializerInterface::class),
                    $app->make(GraphQlSerializerContextBuilder::class),
                    $app->make(Pagination::class)
                ),
                $app->make(CallableProcessor::class),
            );
        });

        $app->singleton(ResolverFactoryInterface::class, function (Application $app) {
            return new ResolverFactory(
                $app->make('api_platform.graphql.state_provider'),
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
                $app->make(FilterInterface::class),
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

            return new Executor($config->get('api-platform.graphql.introspection.enabled') ?? false);
        });

        $app->singleton(GraphiQlController::class, function (Application $app) {
            /** @var ConfigRepository */
            $config = $app['config'];
            $prefix = $config->get('api-platform.routes.prefix') ?? '';

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
                negotiator: $app->make(Negotiator::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, Router $router): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/api-platform.php' => $this->app->configPath('api-platform.php'),
            ], 'laravel-assets');

            $this->publishes([
                __DIR__.'/public' => $this->app->publicPath('vendor/api-platform'),
            ], 'laravel-assets');
        }

        $this->loadViewsFrom(__DIR__.'/resources/views', 'api-platform');

        $config = $this->app['config'];

        if ($config->get('api-platform.graphql.enabled')) {
            $fieldsBuilder = $this->app->make(FieldsBuilderEnumInterface::class);
            $typeBuilder = $this->app->make(ContextAwareTypeBuilderInterface::class);
            $typeBuilder->setFieldsBuilderLocator(new ServiceLocator(['api_platform.graphql.fields_builder' => $fieldsBuilder]));
        }

        if (!$this->shouldRegisterRoutes()) {
            return;
        }

        $routeCollection = new RouteCollection();
        foreach ($resourceNameCollectionFactory->create() as $resourceClass) {
            foreach ($resourceMetadataFactory->create($resourceClass) as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operation) {
                    $uriTemplate = $operation->getUriTemplate();
                    // _format is read by the middleware
                    $uriTemplate = $operation->getRoutePrefix().str_replace('{._format}', '{_format?}', $uriTemplate);
                    $route = (new Route([$operation->getMethod()], $uriTemplate, [ApiPlatformController::class, '__invoke']))
                        ->name($operation->getName())
                        ->setDefaults(['_api_operation_name' => $operation->getName(), '_api_resource_class' => $operation->getClass()]);

                    $route->middleware(ApiPlatformMiddleware::class.':'.$operation->getName());
                    $route->middleware($config->get('api-platform.routes.middleware'));
                    $route->middleware($operation->getMiddleware());

                    $routeCollection->add($route);
                }
            }
        }

        $prefix = $config->get('api-platform.routes.prefix') ?? '';
        $route = new Route(['GET'], $prefix.'/contexts/{shortName?}{_format?}', [ContextAction::class, '__invoke']);
        $route->name('api_jsonld_context')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);
        // Maybe that we can alias Symfony Request to Laravel Request within the provider ?
        $route = new Route(['GET'], $prefix.'/docs{_format?}', function (Request $request, Application $app) {
            $documentationAction = $app->make(DocumentationAction::class);

            return $documentationAction->__invoke($request);
        });
        $route->name('api_doc')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);

        $route = new Route(['GET'], $prefix.'/{index?}{_format?}', function (Request $request, Application $app) {
            $entrypointAction = $app->make(EntrypointAction::class);

            return $entrypointAction->__invoke($request);
        });
        $route->where('index', 'index');
        $route->name('api_entrypoint')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);
        $route = new Route(['GET'], $prefix.'/.well-known/genid/{id}', function (): void {
            throw new NotExposedHttpException('This route is not exposed on purpose. It generates an IRI for a collection resource without identifier nor item operation.');
        });
        $route->name('api_genid')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);

        if ($config->get('api-platform.graphql.enabled')) {
            $route = new Route(['POST', 'GET'], $prefix.'/graphql', function (Application $app, Request $request) {
                $entrypointAction = $app->make(GraphQlEntrypointController::class);

                return $entrypointAction->__invoke($request);
            });
            $routeCollection->add($route);

            $route = new Route(['GET'], $prefix.'/graphiql', function (Application $app) {
                $controller = $app->make(GraphiQlController::class);

                return $controller->__invoke();
            });
            $routeCollection->add($route);
        }

        $router->setRoutes($routeCollection);
    }

    private function shouldRegisterRoutes(): bool
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return false;
        }

        return true;
    }
}
