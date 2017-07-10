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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\InvalidResourceException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
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
    const ROUTE_NAME_PREFIX = 'api_';
    const DEFAULT_ACTION_PATTERN = 'api_platform.action.';
    const SUBRESOURCE_SUFFIX = '_get_subresource';

    private $fileLoader;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $resourceNameCollectionFactory;
    private $resourceMetadataFactory;
    private $operationPathResolver;
    private $container;
    private $formats;
    private $resourceClassDirectories;

    public function __construct(KernelInterface $kernel, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataFactoryInterface $resourceMetadataFactory, OperationPathResolverInterface $operationPathResolver, ContainerInterface $container, array $formats, array $resourceClassDirectories = [], PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory = null, PropertyMetadataFactoryInterface $propertyMetadataFactory = null)
    {
        $this->fileLoader = new XmlFileLoader(new FileLocator($kernel->locateResource('@ApiPlatformBundle/Resources/config/routing')));
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->container = $container;
        $this->formats = $formats;
        $this->resourceClassDirectories = $resourceClassDirectories;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
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
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            $resourceShortName = $resourceMetadata->getShortName();

            if (null === $resourceShortName) {
                throw new InvalidResourceException(sprintf('Resource %s has no short name defined.', $resourceClass));
            }

            if (null !== $collectionOperations = $resourceMetadata->getCollectionOperations()) {
                foreach ($collectionOperations as $operationName => $operation) {
                    $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $resourceShortName, OperationType::COLLECTION);
                }
            }

            if (null !== $itemOperations = $resourceMetadata->getItemOperations()) {
                foreach ($itemOperations as $operationName => $operation) {
                    $this->addRoute($routeCollection, $resourceClass, $operationName, $operation, $resourceShortName, OperationType::ITEM);
                }
            }

            $this->computeSubresourceOperations($routeCollection, $resourceClass);
        }

        return $routeCollection;
    }

    /**
     * Handles subresource operations recursively and declare their corresponding routes.
     *
     * @param RouteCollection $routeCollection
     * @param string          $resourceClass
     * @param string          $rootResourceClass null on the first iteration, it then keeps track of the origin resource class
     * @param array           $parentOperation   the previous call operation
     */
    private function computeSubresourceOperations(RouteCollection $routeCollection, string $resourceClass, string $rootResourceClass = null, array $parentOperation = null, array $visited = [])
    {
        if (null === $this->propertyNameCollectionFactory || null === $this->propertyMetadataFactory) {
            return;
        }

        if (null === $rootResourceClass) {
            $rootResourceClass = $resourceClass;
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);

            if (!$propertyMetadata->hasSubresource()) {
                continue;
            }

            $subresource = $propertyMetadata->getSubresource();

            $operation = [
                'property' => $property,
                'collection' => $subresource->isCollection(),
            ];

            $visiting = "$rootResourceClass $resourceClass $property {$subresource->isCollection()} {$subresource->getResourceClass()}";

            if (in_array($visiting, $visited, true)) {
                continue;
            }

            $visited[] = $visiting;

            if (null === $parentOperation) {
                $rootResourceMetadata = $this->resourceMetadataFactory->create($rootResourceClass);
                $rootShortname = $rootResourceMetadata->getShortName();

                $operation['identifiers'] = [['id', $rootResourceClass]];
                $operation['route_name'] = RouteNameGenerator::generate('get', $rootShortname, OperationType::SUBRESOURCE, $operation);
                $operation['path'] = $this->operationPathResolver->resolveOperationPath($rootShortname, $operation, OperationType::SUBRESOURCE, $operation['route_name']);
            } else {
                $operation['identifiers'] = $parentOperation['identifiers'];
                $operation['identifiers'][] = [$parentOperation['property'], $resourceClass];
                $operation['route_name'] = str_replace('get'.RouteNameGenerator::SUBRESOURCE_SUFFIX, RouteNameGenerator::routeNameResolver($property, $operation['collection']).'_get'.RouteNameGenerator::SUBRESOURCE_SUFFIX, $parentOperation['route_name']);
                $operation['path'] = $this->operationPathResolver->resolveOperationPath($parentOperation['path'], $operation, OperationType::SUBRESOURCE, $operation['route_name']);
            }

            $route = new Route(
                $operation['path'],
                [
                    '_controller' => self::DEFAULT_ACTION_PATTERN.'get_subresource',
                    '_format' => null,
                    '_api_resource_class' => $subresource->getResourceClass(),
                    '_api_subresource_operation_name' => $operation['route_name'],
                    '_api_subresource_context' => [
                        'property' => $operation['property'],
                        'identifiers' => $operation['identifiers'],
                        'collection' => $subresource->isCollection(),
                    ],
                ],
                [],
                [],
                '',
                [],
                ['GET']
            );

            $routeCollection->add($operation['route_name'], $route);

            $this->computeSubresourceOperations($routeCollection, $subresource->getResourceClass(), $rootResourceClass, $operation, $visited);
        }
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
     *
     * @param RouteCollection $routeCollection
     */
    private function loadExternalFiles(RouteCollection $routeCollection)
    {
        $routeCollection->addCollection($this->fileLoader->load('api.xml'));

        if (isset($this->formats['jsonld'])) {
            $routeCollection->addCollection($this->fileLoader->load('jsonld.xml'));
        }
    }

    /**
     * Creates and adds a route for the given operation to the route collection.
     *
     * @param RouteCollection $routeCollection
     * @param string          $resourceClass
     * @param string          $operationName
     * @param array           $operation
     * @param string          $resourceShortName
     * @param string          $operationType
     *
     * @throws RuntimeException
     */
    private function addRoute(RouteCollection $routeCollection, string $resourceClass, string $operationName, array $operation, string $resourceShortName, string $operationType)
    {
        if (isset($operation['route_name'])) {
            return;
        }

        if (!isset($operation['method'])) {
            throw new RuntimeException('Either a "route_name" or a "method" operation attribute must exist.');
        }

        if (null === $controller = $operation['controller'] ?? null) {
            $controller = sprintf('%s%s_%s', self::DEFAULT_ACTION_PATTERN, strtolower($operation['method']), $operationType);

            if (!$this->container->has($controller)) {
                throw new RuntimeException(sprintf('There is no builtin action for the %s %s operation. You need to define the controller yourself.', $operationType, $operation['method']));
            }
        }

        $route = new Route(
            $this->operationPathResolver->resolveOperationPath($resourceShortName, $operation, $operationType, $operationName),
            [
                '_controller' => $controller,
                '_format' => null,
                '_api_resource_class' => $resourceClass,
                sprintf('_api_%s_operation_name', $operationType) => $operationName,
            ],
            [],
            [],
            '',
            [],
            [$operation['method']]
        );

        $routeCollection->add(RouteNameGenerator::generate($operationName, $resourceShortName, $operationType), $route);
    }
}
