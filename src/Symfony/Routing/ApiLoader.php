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

use ApiPlatform\Metadata\Exception\RuntimeException;
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
    public const DEFAULT_ACTION_PATTERN = 'api_platform.action.';

    private readonly XmlFileLoader $fileLoader;

    public function __construct(KernelInterface $kernel, private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly ContainerInterface $container, private readonly array $formats, private readonly array $resourceClassDirectories = [], private readonly bool $graphqlEnabled = false, private readonly bool $entrypointEnabled = true, private readonly bool $docsEnabled = true, private readonly bool $graphiQlEnabled = false, private readonly bool $graphQlPlaygroundEnabled = false)
    {
        /** @var string[]|string $paths */
        $paths = $kernel->locateResource('@ApiPlatformBundle/Resources/config/routing');
        $this->fileLoader = new XmlFileLoader(new FileLocator($paths));
    }

    /**
     * {@inheritdoc}
     */
    public function load(mixed $data, ?string $type = null): RouteCollection
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

                    if (SkolemIriConverter::$skolemUriTemplate === $operation->getUriTemplate()) {
                        continue;
                    }

                    $path = ($operation->getRoutePrefix() ?? '').$operation->getUriTemplate();
                    foreach ($operation->getUriVariables() ?? [] as $parameterName => $link) {
                        if (!$expandedValue = $link->getExpandedValue()) {
                            continue;
                        }

                        $path = str_replace(\sprintf('{%s}', $parameterName), $expandedValue, $path);
                    }

                    // Within Symfony .{_format} is a special parameter but the rfc6570 specifies label expansion with a dot operator
                    if (str_ends_with($path, '{._format}')) {
                        $path = str_replace('{._format}', '.{_format}', $path);
                    }

                    if ($controller = $operation->getController()) {
                        $controllerId = explode('::', $controller, 2)[0];
                        if (!$this->container->has($controllerId)) {
                            throw new RuntimeException(\sprintf('Operation "%s" is defining an unknown service as controller "%s". Make sure it is properly registered in the dependency injection container.', $operationName, $controllerId));
                        }
                    }

                    $route = new Route(
                        $path,
                        [
                            '_controller' => $controller ?? 'api_platform.action.placeholder',
                            '_format' => null,
                            '_stateless' => $operation->getStateless(),
                            '_api_resource_class' => $resourceClass,
                            '_api_operation_name' => $operationName,
                        ] + ($operation->getDefaults() ?? []),
                        $operation->getRequirements() ?? [],
                        $operation->getOptions() ?? [],
                        $operation->getHost() ?? '',
                        $operation->getSchemes() ?? [],
                        [$operation->getMethod() ?? 'GET'],
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
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'api_platform' === $type;
    }

    /**
     * Load external files.
     */
    private function loadExternalFiles(RouteCollection $routeCollection): void
    {
        $routeCollection->addCollection($this->fileLoader->load('genid.xml'));
        $routeCollection->addCollection($this->fileLoader->load('errors.xml'));

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
