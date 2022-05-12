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

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
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
    private $container;
    private $formats;
    private $resourceClassDirectories;
    private $graphqlEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;
    private $entrypointEnabled;
    private $docsEnabled;
    private $identifiersExtractor;

    public function __construct(KernelInterface $kernel, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, ContainerInterface $container, array $formats, array $resourceClassDirectories = [], bool $graphqlEnabled = false, bool $entrypointEnabled = true, bool $docsEnabled = true, bool $graphiQlEnabled = false, bool $graphQlPlaygroundEnabled = false)
    {
        /** @var string[]|string $paths */
        $paths = $kernel->locateResource('@ApiPlatformBundle/Resources/config/routing');
        $this->fileLoader = new XmlFileLoader(new FileLocator($paths));
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->container = $container;
        $this->formats = $formats;
        $this->resourceClassDirectories = $resourceClassDirectories;
        $this->graphqlEnabled = $graphqlEnabled;
        $this->graphiQlEnabled = $graphiQlEnabled;
        $this->graphQlPlaygroundEnabled = $graphQlPlaygroundEnabled;
        $this->entrypointEnabled = $entrypointEnabled;
        $this->docsEnabled = $docsEnabled;
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
            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resourceMetadata) {
                foreach ($resourceMetadata->getOperations() as $operationName => $operation) {
                    if ($operation->getRouteName()) {
                        continue;
                    }

                    // $legacyDefaults = [];

                    // if ($operation->getExtraProperties()['is_legacy_subresource'] ?? false) {
                    //     $legacyDefaults['_api_subresource_operation_name'] = $operationName;
                    //     $legacyDefaults['_api_subresource_context'] = [
                    //         'property' => $operation->getExtraProperties()['legacy_subresource_property'],
                    //         'identifiers' => $operation->getExtraProperties()['legacy_subresource_identifiers'],
                    //         'collection' => $operation instanceof CollectionOperationInterface,
                    //         'operationId' => $operation->getExtraProperties()['legacy_subresource_operation_name'] ?? null,
                    //     ];
                    //     $legacyDefaults['_api_identifiers'] = $operation->getExtraProperties()['legacy_subresource_identifiers'];
                    // } elseif ($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) {
                    //     $legacyDefaults[sprintf('_api_%s_operation_name', $operation instanceof CollectionOperationInterface ? OperationType::COLLECTION : OperationType::ITEM)] = $operationName;
                    //     $legacyDefaults['_api_identifiers'] = [];
                    //     // Legacy identifiers
                    //     $hasCompositeIdentifier = false;
                    //     foreach ($operation->getUriVariables() ?? [] as $parameterName => $identifiedBy) {
                    //         $hasCompositeIdentifier = $identifiedBy->getCompositeIdentifier();
                    //         foreach ($identifiedBy->getIdentifiers() ?? [] as $identifier) {
                    //             $legacyDefaults['_api_identifiers'][] = $identifier;
                    //         }
                    //     }
                    //     $legacyDefaults['_api_has_composite_identifier'] = $hasCompositeIdentifier;
                    // }

                    $path = ($operation->getRoutePrefix() ?? '').$operation->getUriTemplate();
                    foreach ($operation->getUriVariables() ?? [] as $parameterName => $link) {
                        if (!$expandedValue = $link->getExpandedValue()) {
                            continue;
                        }

                        $path = str_replace(sprintf('{%s}', $parameterName), $expandedValue, $path);
                    }

                    $route = new Route(
                        $path,
                        [
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
}
