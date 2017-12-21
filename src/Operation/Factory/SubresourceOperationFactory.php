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

namespace ApiPlatform\Core\Operation\Factory;

use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Operation\PathSegmentNameGeneratorInterface;

/**
 * @internal
 */
final class SubresourceOperationFactory implements SubresourceOperationFactoryInterface
{
    const SUBRESOURCE_SUFFIX = '_subresource';
    const FORMAT_SUFFIX = '.{_format}';
    const ROUTE_OPTIONS = ['defaults' => [], 'requirements' => [], 'options' => [], 'host' => '', 'schemes' => [], 'condition' => ''];

    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $pathSegmentNameGenerator;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory, PropertyMetadataFactoryInterface $propertyMetadataFactory, PathSegmentNameGeneratorInterface $pathSegmentNameGenerator)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->pathSegmentNameGenerator = $pathSegmentNameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $tree = [];
        $this->computeSubresourceOperations($resourceClass, $tree);

        return $tree;
    }

    /**
     * Handles subresource operations recursively and declare their corresponding routes.
     *
     * @param string $resourceClass
     * @param array  $tree
     * @param string $rootResourceClass null on the first iteration, it then keeps track of the origin resource class
     * @param array  $parentOperation   the previous call operation
     * @param array  $visited
     * @param int    $depth             the number of visited
     * @param int    $maxDepth
     */
    private function computeSubresourceOperations(string $resourceClass, array &$tree, string $rootResourceClass = null, array $parentOperation = null, array $visited = [], int $depth = 0, int $maxDepth = null)
    {
        if (null === $rootResourceClass) {
            $rootResourceClass = $resourceClass;
        }

        foreach ($this->propertyNameCollectionFactory->create($resourceClass) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resourceClass, $property);

            if (!$propertyMetadata->hasSubresource()) {
                continue;
            }

            $subresource = $propertyMetadata->getSubresource();
            $subresourceClass = $subresource->getResourceClass();
            $subresourceMetadata = $this->resourceMetadataFactory->create($subresourceClass);

            $visiting = "$resourceClass $property $subresourceClass";

            // Handle maxDepth
            if ($rootResourceClass === $resourceClass) {
                $maxDepth = $subresource->getMaxDepth();
            }

            if (null !== $maxDepth && $depth >= $maxDepth) {
                break;
            }
            if (isset($visited[$visiting])) {
                continue;
            }

            $rootResourceMetadata = $this->resourceMetadataFactory->create($rootResourceClass);
            $operationName = 'get';
            $operation = [
                'property' => $property,
                'collection' => $subresource->isCollection(),
                'resource_class' => $subresourceClass,
                'shortNames' => [$subresourceMetadata->getShortName()],
            ];

            if (null === $parentOperation) {
                $rootShortname = $rootResourceMetadata->getShortName();
                $operation['identifiers'] = [['id', $rootResourceClass, true]];
                $operation['operation_name'] = sprintf(
                    '%s_%s%s',
                    RouteNameGenerator::inflector($operation['property'], $operation['collection'] ?? false),
                    $operationName,
                    self::SUBRESOURCE_SUFFIX
                );

                $subresourceOperation = $rootResourceMetadata->getSubresourceOperations()[$operation['operation_name']] ?? [];

                $operation['route_name'] = sprintf(
                    '%s%s_%s',
                    RouteNameGenerator::ROUTE_NAME_PREFIX,
                    RouteNameGenerator::inflector($rootShortname),
                    $operation['operation_name']
                );

                $operation['path'] = $subresourceOperation['path'] ?? sprintf(
                    '/%s/{id}/%s%s',
                    $this->pathSegmentNameGenerator->getSegmentName($rootShortname, true),
                    $this->pathSegmentNameGenerator->getSegmentName($operation['property'], $operation['collection']),
                    self::FORMAT_SUFFIX
                );

                if (!\in_array($rootShortname, $operation['shortNames'], true)) {
                    $operation['shortNames'][] = $rootShortname;
                }
            } else {
                $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
                $operation['identifiers'] = $parentOperation['identifiers'];
                $operation['identifiers'][] = [$parentOperation['property'], $resourceClass, $parentOperation['collection']];

                $operation['operation_name'] = str_replace('get'.self::SUBRESOURCE_SUFFIX, RouteNameGenerator::inflector($property, $operation['collection']).'_get'.self::SUBRESOURCE_SUFFIX, $parentOperation['operation_name']);

                $operation['route_name'] = str_replace($parentOperation['operation_name'], $operation['operation_name'], $parentOperation['route_name']);

                if (!\in_array($resourceMetadata->getShortName(), $operation['shortNames'], true)) {
                    $operation['shortNames'][] = $resourceMetadata->getShortName();
                }

                $subresourceOperation = $rootResourceMetadata->getSubresourceOperations()[$operation['operation_name']] ?? [];

                if (isset($subresourceOperation['path'])) {
                    $operation['path'] = $subresourceOperation['path'];
                } else {
                    $operation['path'] = str_replace(self::FORMAT_SUFFIX, '', $parentOperation['path']);
                    if ($parentOperation['collection']) {
                        list($key) = end($operation['identifiers']);
                        $operation['path'] .= sprintf('/{%s}', $key);
                    }
                    $operation['path'] .= sprintf('/%s%s', $this->pathSegmentNameGenerator->getSegmentName($property, $operation['collection']), self::FORMAT_SUFFIX);
                }
            }

            foreach (self::ROUTE_OPTIONS as $routeOption => $defaultValue) {
                $operation[$routeOption] = $subresourceOperation[$routeOption] ?? $defaultValue;
            }

            $tree[$operation['route_name']] = $operation;

            $this->computeSubresourceOperations($subresourceClass, $tree, $rootResourceClass, $operation, $visited + [$visiting => true], ++$depth, $maxDepth);
        }
    }
}
