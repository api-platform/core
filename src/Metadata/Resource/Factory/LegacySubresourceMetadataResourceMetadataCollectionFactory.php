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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * We have to compute a local cache having all the resource => subresource matching.
 *
 * @deprecated
 */
final class LegacySubresourceMetadataResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use DeprecationMetadataTrait;
    private $decorated;
    private $resourceNameCollectionFactory;
    private $subresourceOperationFactory;
    private $localCache = [];

    public function __construct(SubresourceOperationFactoryInterface $subresourceOperationFactory, ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        if (0 === \count($this->localCache)) {
            $this->computeSubresourceCache();
        }

        if (!isset($this->localCache[$resourceClass])) {
            return $resourceMetadataCollection;
        }

        $defaults = $resourceMetadataCollection[0] ?? new ApiResource();
        foreach ($this->localCache[$resourceClass] as $resource) {
            $operations = iterator_to_array($resource->getOperations());
            $operation = current($operations);
            $operationName = key($operations);

            foreach (get_class_methods($defaults) as $methodName) {
                if (0 !== strpos($methodName, 'get')) {
                    continue;
                }

                if (!method_exists($operation, $methodName)) {
                    continue;
                }

                $operationValue = $operation->{$methodName}();
                if (null !== $operationValue) {
                    continue;
                }

                if (($value = $defaults->{$methodName}()) !== null) {
                    $operation = $operation->{'with'.substr($methodName, 3)}($value);
                }
            }

            $resourceMetadataCollection[] = $resource->withOperations(new Operations([$operationName => $operation->withName($operationName)]));
        }

        return $resourceMetadataCollection;
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
                $previousParameterName = null;
                foreach ($subresourceMetadata['identifiers'] as $parameterName => [$class, $property, $isPresent]) {
                    if (!$isPresent) {
                        continue;
                    }

                    $identifiers[$parameterName] = (new Link())->withFromClass($class)->withIdentifiers([$property])->withParameterName($parameterName)->withCompositeIdentifier(false);
                    if ($previousParameterName) {
                        $identifiers[$previousParameterName] = $identifiers[$previousParameterName]->withFromProperty($parameterName);
                    }

                    $previousParameterName = $parameterName;
                }

                $extraProperties = ['is_legacy_subresource' => true];
                if ($subresourceMetadata['property']) {
                    $extraProperties['legacy_subresource_property'] = $subresourceMetadata['property'];
                }

                if ($subresourceMetadata['identifiers']) {
                    $extraProperties['legacy_subresource_identifiers'] = $subresourceMetadata['identifiers'];
                    unset($subresourceMetadata['identifiers']);
                }

                $resource = (new ApiResource())->withExtraProperties($extraProperties)->withUriVariables($identifiers)->withStateless(false);
                /* @var HttpOperation $operation */
                $operation = ($subresourceMetadata['collection'] ? new GetCollection() : new Get());
                $operation = $operation->withUriVariables($identifiers);
                $operation = $operation->withExtraProperties($extraProperties + ['legacy_subresource_operation_name' => $subresourceMetadata['route_name']]);

                if ($subresourceMetadata['path'] && $operation instanceof HttpOperation) {
                    $resource = $resource->withUriTemplate($subresourceMetadata['path']);
                    $operation = $operation->withUriTemplate($subresourceMetadata['path']);
                }

                if ($subresourceMetadata['shortNames'][0]) {
                    $resource = $resource->withShortName($subresourceMetadata['shortNames'][0]);
                    $operation = $operation->withShortName($subresourceMetadata['shortNames'][0]);
                }

                if ($subresourceMetadata['resource_class']) {
                    $resource = $resource->withClass($subresourceMetadata['resource_class']);
                    $operation = $operation->withClass($subresourceMetadata['resource_class']);
                }

                foreach ($subresourceMetadata as $key => $value) {
                    if ('route_name' === $key) {
                        continue;
                    }
                    $resource = $this->setAttributeValue($resource, $key, $value);
                    $operation = $this->setAttributeValue($operation, $key, $value);
                }

                $resource = $resource->withOperations(new Operations([
                    $subresourceMetadata['route_name'] => $operation,
                ]));

                if ($subresourceMetadata['controller']) { // manage null values from subresources
                    $resource = $resource->withController($subresourceMetadata['controller']);
                }

                $this->localCache[$resource->getClass()][] = $resource;
            }
        }
    }

    /**
     * @param HttpOperation|GraphQlOperation|ApiResource $operation
     * @param mixed                                      $value
     *
     * @return HttpOperation|GraphQlOperation|ApiResource
     */
    private function setAttributeValue($operation, string $key, $value)
    {
        [$camelCaseKey, $value] = $this->getKeyValue($key, $value);
        $methodName = 'with'.ucfirst($camelCaseKey);

        if (method_exists($operation, $methodName) && null !== $value) {
            return $operation->{$methodName}($value);
        }

        return $operation;
    }
}
