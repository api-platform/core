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

namespace ApiPlatform\Symfony\Routing;

use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Exception\InvalidResourceException;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\PathResolver\OperationPathResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\XmlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Loads Resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ApiLoader extends Loader
{
    /**
     * @deprecated since version 2.1, to be removed in 3.0. Use {@see RouteNameGenerator::ROUTE_NAME_PREFIX} instead.
     */
    public const ROUTE_NAME_PREFIX = 'api_';
    public const DEFAULT_ACTION_PATTERN = 'api_platform.action.';

    private $fileLoader;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $operationPathResolver;
    private $container;
    private $formats;
    private $resourceClassDirectories;
    private $subresourceOperationFactory;
    private $graphqlEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;
    private $entrypointEnabled;
    private $docsEnabled;
    private $identifiersExtractor;

    public function __construct(KernelInterface $kernel, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, $resourceMetadataFactory, OperationPathResolverInterface $operationPathResolver, ContainerInterface $container, array $formats, array $resourceClassDirectories = [], SubresourceOperationFactoryInterface $subresourceOperationFactory = null, bool $graphqlEnabled = false, bool $entrypointEnabled = true, bool $docsEnabled = true, bool $graphiQlEnabled = false, bool $graphQlPlaygroundEnabled = false, IdentifiersExtractorInterface $identifiersExtractor = null)
    {
        /** @var string[]|string $paths */
        $paths = $kernel->locateResource('@ApiPlatformBundle/Resources/config/routing');
        $this->fileLoader = new XmlFileLoader(new FileLocator($paths));
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;

        if ($resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->container = $container;
        $this->formats = $formats;
        $this->resourceClassDirectories = $resourceClassDirectories;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->graphqlEnabled = $graphqlEnabled;
        $this->graphiQlEnabled = $graphiQlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->entrypointEnabled = $entrypointEnabled;
        $this->docsEnabled = $docsEnabled;
        $this->identifiersExtractor = $identifiersExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $type = null): RouteCollection
    {
        $routeCollection = new RouteCollection();
        foreach ($this->resourceClassDirectories as $directory) {
            $routeCollection->addResource(new DirectoryResource($directory, '/\.php$/'));
        }

        $this->loadExternalFiles($routeCollection);
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            if ($this->resourceMetadataFactory instanceof ResourceMetadataFactoryInterface) {
                $this->loadLegacyMetadata($routeCollection, $resourceClass);
                $this->loadLegacySubresources($routeCollection, $resourceClass);
                continue;
            }

            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if ($operation->getRouteName()) {
                        continue;
                    }

                    if (SkolemIriConverter::$skolemUriTemplate === $operation->getUriTemplate()) {
                        continue;
                    }

                    $legacyDefaults = [];

                    if ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) {
                        $legacyDefaults['_api_subresource_operation_name'] = $operationName;
                        $legacyDefaults['_api_subresource_context'] = [
                            'property' => $operation->getExtraProperties()['legacy_subresource_property'],
                            'identifiers' => $operation->getExtraProperties()['legacy_subresource_identifiers'],
                            'collection' => $operation instanceof CollectionOperationInterface,
                            'operationId' => $operation->getExtraProperties()['legacy_subresource_operation_name'] ?? null,
                        ];
                        $legacyDefaults['_api_identifiers'] = $operation->getExtraProperties()['legacy_subresource_identifiers'];
                    } elseif ($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) {
                        $legacyDefaults[sprintf('_api_%s_operation_name', $operation instanceof CollectionOperationInterface ? OperationType::COLLECTION : OperationType::ITEM)] = $operationName;
                        $legacyDefaults['_api_identifiers'] = [];
                        // Legacy identifiers
                        $hasCompositeIdentifier = false;
                        foreach ($operation->getUriVariables() ?? [] as $parameterName => $identifiedBy) {
                            $hasCompositeIdentifier = $identifiedBy->getCompositeIdentifier();
                            foreach ($identifiedBy->getIdentifiers() ?? [] as $identifier) {
                                $legacyDefaults['_api_identifiers'][] = $identifier;
                            }
                        }
                        $legacyDefaults['_api_has_composite_identifier'] = $hasCompositeIdentifier;
                    }

                    $path = ($operation->getRoutePrefix() ?? '').$operation->getUriTemplate();
                    foreach ($operation->getUriVariables() ?? [] as $parameterName => $link) {
                        if (!$expandedValue = $link->getExpandedValue()) {
                            continue;
                        }

                        $path = str_replace(sprintf('{%s}', $parameterName), $expandedValue, $path);
                    }

                    $route = new Route(
                        $path,
                        $legacyDefaults + [
                            '_controller' => $operation->getController() ?? 'api_platform.action.placeholder',
                            '_format' => null,
                            '_stateless' => $operation->getStateless(),
                            '_api_resource_class' => $resourceClass,
                            '_api_operation_name' => $operationName,
                        ] + ($operation->getDefaults() ?? []),
                        $operation->getRequirements() ?? [],
                        $operation->getOptions() ?? [],
                        $operation->getHost() ?? '',
                        $operation->getSchemes() ?? [],
                        [$operation->getMethod() ?? HttpOperation::METHOD_GET],
                        $operation->getCondition() ?? ''
                    );

                    $routeCollection->add($operationName, $route);
                }
            }
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'api_platform' === $type;
    }

    /**
     * Load external files.
     */
    private function loadExternalFiles(RouteCollection $routeCollection): void
    {
        $routeCollection->addCollection($this->fileLoader->load('genid.xml'));

        if ($this->entrypointEnabled) {
            $routeCollection->addCollection($this->fileLoader->load('api.xml'));
        }

        if ($this->docsEnabled) {
            $routeCollection->addCollection($this->fileLoader->load('docs.xml'));
        }

        if ($this->graphqlEnabled) {
            $graphqlCollection = $this->fileLoader->load('graphql/graphql.xml');
            $graphqlCollection->addDefaults(['_graphql' => true]);
            $routeCollection->addCollection($graphqlCollection);
        }

        if ($this->graphiQlEnabled) {
            $graphiQlCollection = $this->fileLoader->load('graphql/graphiql.xml');
            $graphiQlCollection->addDefaults(['_graphql' => true]);
            $routeCollection->addCollection($graphiQlCollection);
        }

        if ($this->graphQlPlaygroundEnabled) {
            $graphQlPlaygroundCollection = $this->fileLoader->load('graphql/graphql_playground.xml');
            $graphQlPlaygroundCollection->addDefaults(['_graphql' => true]);
            $routeCollection->addCollection($graphQlPlaygroundCollection);
        }

        if (isset($this->formats['jsonld'])) {
            $routeCollection->addCollection($this->fileLoader->load('jsonld.xml'));
        }
    }

    /**
     * TODO: remove in 3.0.
     */
    private function loadLegacyMetadata(RouteCollection $routeCollection, string $resourceClass)
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceShortName = $resourceMetadata->getShortName();

        if (null === $resourceShortName) {
            throw new InvalidResourceException(sprintf('Resource %s has no short name defined.', $resourceClass));
        }

        if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
            foreach ($collectionOperations as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $resourceMetadata, OperationType::COLLECTION);
            }
        }

        if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
            foreach ($itemOperations as $operationName => $operation) {
                $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $resourceMetadata, OperationType::ITEM);
            }
        }
    }

    /**
     * TODO: remove in 3.0.
     */
    private function loadLegacySubresources(RouteCollection $routeCollection, string $resourceClass)
    {
        if (null === $this->subresourceOperationFactory) {
            return;
        }

        foreach ($this->subresourceOperationFactory->create($resourceClass) as $operationId => $operation) {
            if (null === $controller = $operation['controller'] ?? null) {
                $controller = self::DEFAULT_ACTION_PATTERN.'get_subresource';

                if (!$this->container->has($controller)) {
                    throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', OperationType::SUBRESOURCE, 'GET'));
                }
            }

            $routeCollection->add($operation['route_name'], new Route(
                $operation['path'],
                [
                    '_controller' => $controller,
                    '_format' => $operation['defaults']['_format'] ?? null,
                    '_stateless' => $operation['stateless'] ?? null,
                    '_api_resource_class' => $operation['resource_class'],
                    '_api_identifiers' => $operation['identifiers'],
                    '_api_has_composite_identifier' => false,
                    '_api_subresource_operation_name' => $operation['route_name'],
                    '_api_subresource_context' => [
                        'property' => $operation['property'],
                        'identifiers' => $operation['identifiers'],
                        'collection' => $operation['collection'],
                        'operationId' => $operationId,
                    ],
                ] + ($operation['defaults'] ?? []),
                $operation['requirements'] ?? [],
                $operation['options'] ?? [],
                $operation['host'] ?? '',
                $operation['schemes'] ?? [],
                ['GET'],
                $operation['condition'] ?? ''
            ));
        }
    }

    /**
     * Creates and adds a route for the given operation to the route collection.
     *
     * @throws RuntimeException
     */
    private function addRoute(RouteCollection $routeCollection, string $resourceClass, string $operationName, array $operation, ResourceMetadata $resourceMetadata, string $operationType): void
    {
        $resourceShortName = $resourceMetadata->getShortName();

        if (isset($operation['route_name'])) {
            if (!isset($operation['method'])) {
                @trigger_error(sprintf('Not setting the "method" attribute is deprecated and will not be supported anymore in API Platform 3.0, set it for the %s operation "%s" of the class "%s".', OperationType::COLLECTION === $operationType ? 'collection' : 'item', $operationName, $resourceClass), \E_USER_DEPRECATED);
            }

            return;
        }

        if (!isset($operation['method'])) {
            throw new RuntimeException(sprintf('Either a "route_name" or a "method" operation attribute must exist for the operation "%s" of the resource "%s".', $operationName, $resourceClass));
        }

        if (null === $controller = $operation['controller'] ?? null) {
            $controller = sprintf('%s%s_%s', self::DEFAULT_ACTION_PATTERN, strtolower($operation['method']), $operationType);

            if (!$this->container->has($controller)) {
                throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', $operationType, $operation['method']));
            }
        }

        if ($resourceMetadata->getItemOperations()) {
            $operation['identifiers'] = (array) ($operation['identifiers'] ?? $resourceMetadata->getAttribute('identifiers', $this->identifiersExtractor ? $this->identifiersExtractor->getIdentifiersFromResourceClass($resourceClass) : ['id']));
        }

        $operation['has_composite_identifier'] = isset($operation['identifiers']) && \count($operation['identifiers']) > 1 ? $resourceMetadata->getAttribute('composite_identifier', true) : false;
        $path = trim(trim($resourceMetadata->getAttribute('route_prefix', '')), '/');
        $path .= $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName);

        $route = new Route(
            $path,
            [
                '_controller' => $controller,
                '_format' => $operation['defaults']['_format'] ?? null,
                '_stateless' => $operation['stateless'] ?? null,
                '_api_resource_class' => $resourceClass,
                '_api_identifiers' => $operation['identifiers'] ?? [],
                '_api_has_composite_identifier' => $operation['has_composite_identifier'] ?? true,
                '_api_exception_to_status' => $operation['exception_to_status'] ?? $resourceMetadata->getAttribute('exception_to_status') ?? [],
                '_api_operation_name' => RouteNameGenerator::generate($operationName, $resourceShortName, $operationType),
                sprintf('_api_%s_operation_name', $operationType) => $operationName,
            ] + ($operation['defaults'] ?? []),
            $operation['requirements'] ?? [],
            $operation['options'] ?? [],
            $operation['host'] ?? '',
            $operation['schemes'] ?? [],
            [$operation['method']],
            $operation['condition'] ?? ''
        );

        $routeCollection->add(RouteNameGenerator::generate($operationName, $resourceShortName, $operationType), $route);
    }
}

class_alias(ApiLoader::class, \ApiPlatform\Core\Bridge\Symfony\Routing\ApiLoader::class);
