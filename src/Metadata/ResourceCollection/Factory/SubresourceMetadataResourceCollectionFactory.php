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

namespace ApiPlatform\Core\Metadata\ResourceCollection\Factory;

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource;

/**
 * We have to compute a local cache having all the resource => subresource matching
 * @deprecated
 */
final class SubresourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $resourceNameCollectionFactory;
    private $subresourceOperationFactory;
    private $localCache = [];

    public function __construct(SubresourceOperationFactoryInterface $subresourceOperationFactory, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceCollectionMetadataFactoryInterface $decorated = null)
    {
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceCollection = [];
        if ($this->decorated) {
            try {
                $parentResourceCollection = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        if (0 === \count($this->localCache)) {
            $this->computeSubresourceCache();
        }

        if (!isset($this->localCache[$resourceClass])) {
            return $parentResourceCollection;
        }

        foreach ($this->localCache[$resourceClass] as $resource) {
            $parentResourceCollection[] = $resource;
        }

        return $parentResourceCollection;
    }

    private function computeSubresourceCache()
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            if (!isset($this->localCache[$resourceClass])) {
                $this->localCache[$resourceClass] = [];
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $subresourceMetadata) {
                if (!isset($this->localCache[$subresourceMetadata['resource_class']])) {
                    $this->localCache[$subresourceMetadata['resource_class']] = [];
                }

                $identifiers = [];
                // Removing the third tuple element
                foreach ($subresourceMetadata['identifiers'] as $parameterName => [$property, $class]) {
                    $identifiers[$parameterName] = [$property, $class];
                }

                $resource = new Resource(
                    uriTemplate: $subresourceMetadata['path'],
                    shortName: $subresourceMetadata['shortNames'][0],
                    operations: [
                        $subresourceMetadata['route_name'] => new Get(
                            uriTemplate: $subresourceMetadata['path'],
                            shortName: $subresourceMetadata['shortNames'][0],
                            identifiers: $identifiers,
                            defaults: $subresourceMetadata['defaults'],
                            requirements: $subresourceMetadata['requirements'],
                            options: $subresourceMetadata['options'],
                            stateless: $subresourceMetadata['stateless'],
                            host: $subresourceMetadata['host'],
                            schemes: $subresourceMetadata['schemes'],
                            condition: $subresourceMetadata['condition'],
                            class: $subresourceMetadata['resource_class'],
                            collection: $subresourceMetadata['collection'],
                        ),
                    ],
                    identifiers: $identifiers,
                    defaults: $subresourceMetadata['defaults'],
                    requirements: $subresourceMetadata['requirements'],
                    options: $subresourceMetadata['options'],
                    stateless: $subresourceMetadata['stateless'],
                    host: $subresourceMetadata['host'],
                    schemes: $subresourceMetadata['schemes'],
                    condition: $subresourceMetadata['condition'],
                    class: $subresourceMetadata['resource_class'],
                );

                if ($subresourceMetadata['controller']) { // manage null values from subresources
                    $resource = $resource->withController($subresourceMetadata['controller']);
                }

                $this->localCache[$resource->getClass()] = $resource;
            }
        }
    }
}
