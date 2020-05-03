<?php

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing\Loader;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
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

    private $kernel;
    private $resourceNameCollectionFactory;
    private $formats;
    private $resourceClassDirectories;
    private $subresourceOperationFactory;
    private $graphqlEnabled;
    private $graphiQlEnabled;
    private $graphQlPlaygroundEnabled;
    private $entrypointEnabled;
    private $docsEnabled;

    public function __construct(
        KernelInterface $kernel,
        ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory,
        array $formats,
        array $resourceClassDirectories = [],
        SubresourceOperationFactoryInterface $subresourceOperationFactory = null,
        bool $graphqlEnabled = false,
        bool $entrypointEnabled = true,
        bool $docsEnabled = true,
        bool $graphiQlEnabled = false,
        bool $graphQlPlaygroundEnabled = false
    ) {
        $this->kernel = $kernel;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->formats = $formats;
        $this->resourceClassDirectories = $resourceClassDirectories;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
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
        $collection = new RouteCollection();
        foreach ($this->resourceClassDirectories as $directory) {
            $collection->addResource(new DirectoryResource($directory, '/\.php$/'));
        }
        $this->loadExternalFiles($collection);
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            $subCollection = $this->import($resourceClass, 'api_resource');
            $collection->addCollection($subCollection);
            if (null === $this->subresourceOperationFactory) {
                continue;
            } 
            $subCollection = $this->import($resourceClass, 'api_subresource');
            $collection->addCollection($subCollection);
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'api_platform' === $type;
    }

    /**
     * Load external files.
     */
    private function loadExternalFiles(RouteCollection $collection): void
    {
        $paths = $this->kernel->locateResource('@ApiPlatformBundle/Resources/config/routing');
        $fileLoader = new XmlFileLoader(
            new FileLocator($paths)
        );

        if ($this->entrypointEnabled) {
            $collection->addCollection($fileLoader->load('api.xml'));
        }
        if ($this->docsEnabled) {
            $collection->addCollection($fileLoader->load('docs.xml'));
        }
        if ($this->graphqlEnabled) {
            $graphqlCollection = $fileLoader->load('graphql/graphql.xml');
            $graphqlCollection->addDefaults(['_graphql' => true]);
            $collection->addCollection($graphqlCollection);
        }
        if ($this->graphiQlEnabled) {
            $graphiQlCollection = $fileLoader->load('graphql/graphiql.xml');
            $graphiQlCollection->addDefaults(['_graphql' => true]);
            $collection->addCollection($graphiQlCollection);
        }
        if ($this->graphQlPlaygroundEnabled) {
            $graphQlPlaygroundCollection = $fileLoader->load('graphql/graphql_playground.xml');
            $graphQlPlaygroundCollection->addDefaults(['_graphql' => true]);
            $collection->addCollection($graphQlPlaygroundCollection);
        }
        if (isset($this->formats['jsonld'])) {
            $collection->addCollection($fileLoader->load('jsonld.xml'));
        }
    }

}
