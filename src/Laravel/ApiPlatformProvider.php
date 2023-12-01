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
use ApiPlatform\Hydra\Serializer\CollectionNormalizer as HydraCollectionNormalizer;
use ApiPlatform\Hydra\Serializer\DocumentationNormalizer as HydraDocumentationNormalizer;
use ApiPlatform\Hydra\Serializer\EntrypointNormalizer as HydraEntrypointNormalizer;
use ApiPlatform\Hydra\State\HydraLinkProcessor;
use ApiPlatform\JsonLd\Action\ContextAction;
use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\JsonLd\ContextBuilder as JsonLdContextBuilder;
use ApiPlatform\JsonLd\ContextBuilderInterface;
use ApiPlatform\JsonLd\Serializer\ItemNormalizer as JsonLdItemNormalizer;
use ApiPlatform\JsonLd\Serializer\ObjectNormalizer as JsonLdObjectNormalizer;
use ApiPlatform\JsonSchema\Metadata\Property\Factory\SchemaPropertyMetadataFactory;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\JsonSchema\TypeFactory;
use ApiPlatform\JsonSchema\TypeFactoryInterface;
use ApiPlatform\Laravel\ApiResource\Error;
use ApiPlatform\Laravel\Controller\ApiPlatformController;
use ApiPlatform\Laravel\Eloquent\Serializer\SerializerContextBuilder as EloquentSerializerContextBuilder;
use ApiPlatform\Laravel\Eloquent\State\CollectionProvider;
use ApiPlatform\Laravel\Eloquent\State\ItemProvider;
use ApiPlatform\Laravel\Eloquent\State\PersistProcessor;
use ApiPlatform\Laravel\Eloquent\State\RemoveProcessor;
use ApiPlatform\Laravel\Metadata\Property\EloquentPropertyMetadataFactory;
use ApiPlatform\Laravel\Metadata\Property\EloquentPropertyNameCollectionMetadataFactory;
use ApiPlatform\Laravel\Metadata\Resource\EloquentResourceCollectionMetadataFactory;
use ApiPlatform\Laravel\Routing\IriConverter;
use ApiPlatform\Laravel\Routing\Router as UrlGeneratorRouter;
use ApiPlatform\Laravel\Routing\SkolemIriConverter;
use ApiPlatform\Laravel\State\SwaggerUiProcessor;
use ApiPlatform\Metadata\IdentifiersExtractor;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactory;
use ApiPlatform\Metadata\Operation\Factory\OperationMetadataFactoryInterface;
use ApiPlatform\Metadata\Operation\PathSegmentNameGeneratorInterface;
use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyMetadataFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyInfoPropertyNameCollectionFactory;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\Factory\SerializerPropertyMetadataFactory;
use ApiPlatform\Metadata\Resource\Factory\AlternateUriResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\AttributesResourceNameCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\FiltersResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\FormatsResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\InputOutputResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\LinkFactory;
use ApiPlatform\Metadata\Resource\Factory\LinkFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\LinkResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\NotExposedOperationResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\OperationNameResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\PhpDocResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\UriTemplateResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\ResourceClassResolver;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Options;
use ApiPlatform\OpenApi\Serializer\OpenApiNormalizer;
use ApiPlatform\Serializer\ItemNormalizer;
use ApiPlatform\Serializer\JsonEncoder;
use ApiPlatform\Serializer\Mapping\Factory\ClassMetadataFactory as SerializerClassMetadataFactory;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\CallableProcessor;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\Provider\ContentNegotiationProvider;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\State\ProviderInterface;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Negotiation\Negotiator;
use phpDocumentor\Reflection\DocBlockFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter as NameConverterCamelCaseToSnakeCaseNameConverter;
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

        // $debug = config('debug') ?? false;
        $defaultContext = [];
        $patchFormats = config('api-platform.patch_formats') ?? ['json' => ['application/merge-patch+json']];
        $formats = config('api-platform.formats') ?? ['jsonld' => ['application/ld+json']];
        $docsFormats = config('api-platform.docs_formats') ?? [
            'jsonopenapi' => ['application/vnd.openapi+json'],
            'json' => ['application/json'],
            'jsonld' => ['application/ld+json'],
            'html' => ['text/html'],
        ];
        $errorFormats = config('api-platform.error_formats') ?? [
            'jsonproblem' => ['application/problem+json'],
        ];
        $pagination = config('api-platform.collection.pagination');
        $graphqlPagination = [];

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

        $this->app->bind(LoaderInterface::class, AnnotationLoader::class);
        $this->app->bind(ClassMetadataFactoryInterface::class, ClassMetadataFactory::class);
        $this->app->singleton(ClassMetadataFactory::class, function () {
            return new ClassMetadataFactory(new AnnotationLoader());
        });

        $this->app->bind(PathSegmentNameGeneratorInterface::class, UnderscorePathSegmentNameGenerator::class);

        $this->app->singleton(ResourceNameCollectionFactoryInterface::class, function () {
            $paths = config('api-platform.resources') ?? [];
            $refl = new \ReflectionClass(Error::class);
            $paths[] = \dirname($refl->getFileName());

            return new AttributesResourceNameCollectionFactory($paths);
        });

        $this->app->bind(ResourceClassResolverInterface::class, ResourceClassResolver::class);
        $this->app->singleton(ResourceClassResolver::class, function (Application $app) {
            return new ResourceClassResolver($app->make(ResourceNameCollectionFactoryInterface::class));
        });

        $this->app->singleton(PropertyMetadataFactoryInterface::class, function (Application $app) {
            return new PropertyInfoPropertyMetadataFactory(
                $app->make(PropertyInfoExtractorInterface::class)
            );
        });

        $this->app->extend(PropertyMetadataFactoryInterface::class, function (PropertyInfoPropertyMetadataFactory $inner, Application $app) {
            return new SchemaPropertyMetadataFactory($app->make(ResourceClassResolverInterface::class), new EloquentPropertyMetadataFactory($app, new SerializerPropertyMetadataFactory(
                new SerializerClassMetadataFactory($app->make(ClassMetadataFactoryInterface::class)),
                $inner,
                $app->make(ResourceClassResolverInterface::class)
            )));
        });

        $this->app->singleton(PropertyNameCollectionFactoryInterface::class, function (Application $app) {
            return new EloquentPropertyNameCollectionMetadataFactory($app, new PropertyInfoPropertyNameCollectionFactory($app->make(PropertyInfoExtractorInterface::class)));
        });

        $this->app->singleton(LinkFactoryInterface::class, function (Application $app) {
            return new LinkFactory(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(ResourceClassResolverInterface::class),
            );
        });

        // TODO: add cached metadata factories
        $this->app->singleton(ResourceMetadataCollectionFactoryInterface::class, function (Application $app) use ($formats, $patchFormats) {
            return new EloquentResourceCollectionMetadataFactory(new AlternateUriResourceMetadataCollectionFactory(
                new FiltersResourceMetadataCollectionFactory(
                    new FormatsResourceMetadataCollectionFactory(
                        new InputOutputResourceMetadataCollectionFactory(
                            new PhpDocResourceMetadataCollectionFactory(
                                new OperationNameResourceMetadataCollectionFactory(
                                    new LinkResourceMetadataCollectionFactory(
                                        $this->app->make(LinkFactoryInterface::class),
                                        new UriTemplateResourceMetadataCollectionFactory(
                                            $this->app->make(LinkFactoryInterface::class),
                                            $this->app->make(PathSegmentNameGeneratorInterface::class),
                                            new NotExposedOperationResourceMetadataCollectionFactory(
                                                $this->app->make(LinkFactoryInterface::class),
                                                // TODO: graphql
                                                new AttributesResourceMetadataCollectionFactory(null, $app->make(LoggerInterface::class), ['routePrefix' => config('api-platform.prefix') ?? '/'], false)
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        $formats,
                        $patchFormats,
                    )
                )
            ));
        });

        $this->app->bind(PropertyAccessorInterface::class, function () {
            return PropertyAccess::createPropertyAccessor();
        });

        $this->app->bind(NameConverterInterface::class, function (Application $app) {
            return new MetadataAwareNameConverter($app->make(ClassMetadataFactoryInterface::class), new NameConverterCamelCaseToSnakeCaseNameConverter());
        });

        $this->app->bind(OperationMetadataFactoryInterface::class, OperationMetadataFactory::class);

        $this->app->tag([ItemProvider::class, CollectionProvider::class], ProviderInterface::class);

        $this->app->singleton(CallableProvider::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ProviderInterface::class));

            return new CallableProvider(new ServiceLocator($tagged));
        });

        $this->app->singleton(ReadProvider::class, function (Application $app) {
            return new ReadProvider($app->make(CallableProvider::class));
        });
        $this->app->singleton(ContentNegotiationProvider::class, function (Application $app) use ($formats, $errorFormats) {
            return new ContentNegotiationProvider($app->make(DeserializeProvider::class), new Negotiator(), $formats, $errorFormats);
        });

        $this->app->singleton(DeserializeProvider::class, function (Application $app) {
            return new DeserializeProvider($app->make(ReadProvider::class), $app->make(SerializerInterface::class), $app->make(SerializerContextBuilderInterface::class));
        });

        $this->app->bind(ProviderInterface::class, ContentNegotiationProvider::class);

        $this->app->tag([RemoveProcessor::class, PersistProcessor::class], ProcessorInterface::class);
        $this->app->singleton(CallableProcessor::class, function (Application $app) {
            $tagged = iterator_to_array($app->tagged(ProcessorInterface::class));
            $tagged['api_platform.swagger_ui.processor'] = $app->make(SwaggerUiProcessor::class);

            return new CallableProcessor(new ServiceLocator($tagged));
        });

        $this->app->singleton(WriteProcessor::class, function (Application $app) {
            return new WriteProcessor($app->make(SerializeProcessor::class), $app->make(CallableProcessor::class));
        });

        $this->app->singleton(SerializerContextBuilder::class, function (Application $app) {
            return new SerializerContextBuilder($app->make(ResourceMetadataCollectionFactoryInterface::class));
        });
        $this->app->bind(SerializerContextBuilderInterface::class, EloquentSerializerContextBuilder::class);
        $this->app->singleton(EloquentSerializerContextBuilder::class, function (Application $app) {
            return new EloquentSerializerContextBuilder($app->make(SerializerContextBuilder::class), $app->make(PropertyNameCollectionFactoryInterface::class));
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
        $this->app->singleton(SkolemIriConverter::class, function (Application $app) {
            return new SkolemIriConverter($app->make(Router::class));
        });

        $this->app->bind(IdentifiersExtractorInterface::class, IdentifiersExtractor::class);
        $this->app->singleton(IdentifiersExtractor::class, function (Application $app) {
            return new IdentifiersExtractor($app->make(ResourceMetadataCollectionFactoryInterface::class), $app->make(ResourceClassResolverInterface::class), $app->make(PropertyNameCollectionFactoryInterface::class), $app->make(PropertyMetadataFactoryInterface::class), $app->make(PropertyAccessorInterface::class));
        });

        $this->app->singleton(IriConverter::class, function (Application $app) {
            return new IriConverter($app->make(CallableProvider::class), $app->make(OperationMetadataFactoryInterface::class), $app->make(UrlGeneratorRouter::class), $app->make(IdentifiersExtractorInterface::class), $app->make(ResourceClassResolverInterface::class), $app->make(ResourceMetadataCollectionFactoryInterface::class));
        });

        $this->app->bind(UrlGeneratorInterface::class, UrlGeneratorRouter::class);
        $this->app->singleton(UrlGeneratorRouter::class, function (Application $app) {
            $request = $app->make('request');
            // https://github.com/laravel/framework/blob/2bfb70bca53e24227a6f921f39d84ba452efd8e0/src/Illuminate/Routing/CompiledRouteCollection.php#L112
            $trimmedRequest = $request->duplicate();
            $parts = explode('?', $request->server->get('REQUEST_URI'), 2);
            $trimmedRequest->server->set(
                'REQUEST_URI', rtrim($parts[0], '/').(isset($parts[1]) ? '?'.$parts[1] : '')
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

        $this->app->singleton(ItemNormalizer::class, function (Application $app) use ($defaultContext) {
            return new ItemNormalizer(
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(IriConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class),
                $app->make(PropertyAccessorInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(LoggerInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                /* $resourceAccessChecker */ null,
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
                // $app->make(ResourceAccessCheckerInterface::class),
                null,
                $defaultContext
            );
        });

        $this->app->singleton(Options::class, function (Application $app) {
            return new Options(title: config('api-platform.title') ?? '');
        });

        $this->app->singleton(DocumentationAction::class, function (Application $app) use ($docsFormats) {
            return new DocumentationAction($app->make(ResourceNameCollectionFactoryInterface::class), config('api-platform.title') ?? '', config('api-platform.description') ?? '', config('api-platform.version') ?? '', $app->make(OpenApiFactoryInterface::class), $app->make(ProviderInterface::class), $app->make(ProcessorInterface::class), $app->make(Negotiator::class), $docsFormats);
        });

        $this->app->singleton(FilterLocator::class, FilterLocator::class);

        $this->app->singleton(EntrypointAction::class, function (Application $app) {
            return new EntrypointAction($app->make(ResourceNameCollectionFactoryInterface::class), $app->make(ProviderInterface::class), $app->make(ProcessorInterface::class), ['jsonld' => ['application/ld+json']]);
        });

        $this->app->singleton(Pagination::class, function () use ($pagination, $graphqlPagination) {
            return new Pagination($pagination, $graphqlPagination);
        });

        $this->app->singleton(PaginationOptions::class, function () use ($pagination) {
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
        $this->app->singleton(OpenApiFactory::class, function (Application $app) use ($formats) {
            return new OpenApiFactory(
                $app->make(ResourceNameCollectionFactoryInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(SchemaFactoryInterface::class),
                $app->make(TypeFactoryInterface::class),
                $app->make(FilterLocator::class),
                $formats,
                null, // ?Options $openApiOptions = null,
                $app->make(PaginationOptions::class), // ?PaginationOptions $paginationOptions = null,
                // ?RouterInterface $router = null
            );
        });

        $this->app->bind(SchemaFactoryInterface::class, SchemaFactory::class);
        $this->app->singleton(SchemaFactory::class, function (Application $app) {
            return new SchemaFactory(
                $app->make(TypeFactoryInterface::class),
                $app->make(ResourceMetadataCollectionFactoryInterface::class),
                $app->make(PropertyNameCollectionFactoryInterface::class),
                $app->make(PropertyMetadataFactoryInterface::class),
                $app->make(NameConverterInterface::class),
                $app->make(ResourceClassResolverInterface::class)
            );
        });

        $this->app->bind(TypeFactoryInterface::class, TypeFactory::class);
        $this->app->singleton(TypeFactory::class, function (Application $app) {
            return new TypeFactory($app->make(ResourceClassResolverInterface::class));
        });

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

        $this->app->singleton(HydraCollectionNormalizer::class, function (Application $app) use ($defaultContext) {
            return new HydraCollectionNormalizer(
                $this->app->make(ContextBuilderInterface::class),
                $this->app->make(ResourceClassResolverInterface::class),
                $this->app->make(IriConverterInterface::class),
                $this->app->make(ResourceMetadataCollectionFactoryInterface::class),
                $defaultContext
            );
        });

        $this->app->bind(SerializerInterface::class, Serializer::class);
        $this->app->bind(NormalizerInterface::class, Serializer::class);
        $this->app->singleton(Serializer::class, function (Application $app) {
            $list = new \SplPriorityQueue();
            $list->insert($app->make(HydraEntrypointNormalizer::class), -800);
            $list->insert($app->make(HydraCollectionNormalizer::class), -800);
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
            // TODO: unused + implement hal/jsonapi ?
            // $list->insert($dataUriNormalizer, -920);
            // $list->insert($unwrappingDenormalizer, 1000);
            // $list->insert($halItemNormalizer, -890);
            // $list->insert($halEntrypointNormalizer, -800);
            // $list->insert($halCollectionNormalizer, -985);
            // $list->insert($halObjectNormalizer, -995);
            // $list->insert($jsonserializableNormalizer, -900);
            // $list->insert($uuidDenormalizer, -895); //Todo ramsey uuid support ?

            // deprecated
            // $list->insert($problemNormalizer, -890);
            // $list->insert($hydraConstraintViolationNormalizer, -780);
            // $list->insert($hydraErrorNormalizer, -800);
            // $list->insert($problemConstraintViolationListNormalizer, -780);
            // $list->insert($problemErrorNormalizer, -810);
            // $list->insert($constraintViolationListNormalizer, -915);
            return new Serializer(iterator_to_array($list), [new JsonEncoder('json'), $app->make(JsonEncoder::class), new JsonEncoder('jsonopenapi')]);
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
                // $app->make(ResourceAccessCheckerInterface::class),
                null
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
                __DIR__.'/config/api-platform.php' => config_path('api-platform.php'),
            ], 'laravel-assets');

            $this->publishes([
                __DIR__.'/public' => public_path('vendor/api-platform'),
            ], 'laravel-assets');
        }

        $this->loadViewsFrom(__DIR__.'/resources/views', 'api-platform');

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
                    $route = new Route([$operation->getMethod()], $uriTemplate, [ApiPlatformController::class, '__invoke']);
                    $route->name($operation->getName());
                    // Another option then to use a middleware, not sure what's best (you then retrieve $request->getRoute() somehow ?)
                    // $route->??? = ['operation' => $operation];
                    $routeCollection->add($route)
                        ->middleware(ApiPlatformMiddleware::class.':'.$operation->getName());
                }
            }
        }

        $route = new Route(['GET'], (config('api-platform.prefix') ?? '').'/contexts/{shortName?}{_format?}', [ContextAction::class, '__invoke']);
        $route->name('api_jsonld_context')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);
        // Maybe that we can alias Symfony Request to Laravel Request within the provider ?
        $route = new Route(['GET'], (config('api-platform.prefix') ?? '').'/docs{_format?}', function (Request $request, Application $app) {
            $documentationAction = $app->make(DocumentationAction::class);

            return $documentationAction->__invoke($request);
        });
        $route->name('api_doc')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);

        $route = new Route(['GET'], (config('api-platform.prefix') ?? '').'/{index?}{_format?}', function (Request $request, Application $app) {
            $entrypointAction = $app->make(EntrypointAction::class);

            return $entrypointAction->__invoke($request);
        });
        $route->name('api_entrypoint')->middleware(ApiPlatformMiddleware::class);
        $routeCollection->add($route);
        $router->setRoutes($routeCollection);
    }

    private function shouldRegisterRoutes(): bool
    {
        if (!config('api-platform.register_routes')) {
            return false;
        }

        if ($this->app->routesAreCached()) {
            return false;
        }

        return true;
    }
}
