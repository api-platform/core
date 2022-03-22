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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Symfony\Routing\RouteNameGenerator;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class LegacyResourceMetadataResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    use DeprecationMetadataTrait;
    private $decorated;
    private $resourceMetadataFactory;
    private $propertyNameCollectionFactory;
    private $propertyMetadataFactory;
    private $defaults;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, PropertyNameCollectionFactoryInterface $propertyNameCollectionFactory = null, PropertyMetadataFactoryInterface $propertyMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->propertyNameCollectionFactory = $propertyNameCollectionFactory;
        $this->propertyMetadataFactory = $propertyMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            return $resourceMetadataCollection;
        }

        $attributes = $resourceMetadata->getAttributes() ?? [];

        foreach ($this->defaults['attributes'] as $key => $value) {
            if (!$value) {
                continue;
            }

            if (!isset($attributes[$key])) {
                $attributes[$key] = $value;
            }
        }

        $resource = (new ApiResource())
            ->withShortName($resourceMetadata->getShortName())
            ->withClass($resourceClass)
            ->withCompositeIdentifier($resourceMetadata->getAttribute('composite_identifier', true))
            ->withExtraProperties(['is_legacy_resource_metadata' => true]);

        if ($description = $resourceMetadata->getDescription()) {
            $resource = $resource->withDescription($description);
        }

        if ($resourceMetadata->getIri()) {
            $resource = $resource->withTypes([$resourceMetadata->getIri()]);
        }

        foreach ($attributes as $key => $value) {
            $resource = $this->setAttributeValue($resource, $key, $value);
        }

        $resource = $this->identifiersToUriVariables($resourceMetadata, $resource);

        $operations = [];
        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM, $resource) as $operationName => $operation) {
            $operationName = RouteNameGenerator::generate($operationName, $resourceMetadata->getShortName(), OperationType::ITEM);
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName())->withName($operationName);
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION, $resource) as $operationName => $operation) {
            $operationName = RouteNameGenerator::generate($operationName, $resourceMetadata->getShortName(), OperationType::COLLECTION);
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName())->withName($operationName);
        }

        if (!$resourceMetadata->getGraphql()) {
            // GraphQl can be null or an empty array, an empty array doesn't disable graphql type creation
            $resourceMetadataCollection[] = $resource->withGraphQlOperations($resourceMetadata->getGraphql())->withOperations(new Operations($operations));

            return $resourceMetadataCollection;
        }

        $graphQlOperations = [];
        foreach ($resourceMetadata->getGraphql() as $operationName => $operation) {
            if (false !== strpos($operationName, 'query') || isset($operation['item_query']) || isset($operation['collection_query'])) {
                $graphQlOperation = (new Query())
                    ->withCollection(isset($operation['collection_query']) || false !== strpos($operationName, 'collection'))
                    ->withName($operationName);
            } else {
                $graphQlOperation = (new Mutation())
                    ->withDescription(ucfirst("{$operationName}s a {$resourceMetadata->getShortName()}."))
                    ->withName($operationName);
            }

            $graphQlOperation = $graphQlOperation
                ->withArgs($operation['args'] ?? null)
                ->withClass($resourceClass)
                ->withResolver($operation['item_query'] ?? $operation['collection_query'] ?? $operation['mutation'] ?? null);

            foreach ($operation as $key => $value) {
                $graphQlOperation = $this->setAttributeValue($graphQlOperation, $key, $value);
            }

            $graphQlOperation = $graphQlOperation->withResource($resource);

            if ('update' === $operationName && $graphQlOperation instanceof Mutation && $graphQlOperation->getMercure()) {
                $graphQlOperations['update_subscription'] = (new Subscription())
                    ->withDescription("Subscribes to the $operationName event of a {$graphQlOperation->getShortName()}.")
                    ->withName('update_subscription')
                    ->withOperation($graphQlOperation);
            }

            $graphQlOperations[$operationName] = $graphQlOperation;
        }

        $resourceMetadataCollection[] = $resource->withOperations(new Operations($operations))->withGraphQlOperations($graphQlOperations);

        return $resourceMetadataCollection;
    }

    private function createOperations(array $operations, string $type, ApiResource $resource): iterable
    {
        $priority = 0;
        foreach ($operations as $operationName => $operation) {
            $newOperation = (new Operation())
                ->withMethod($operation['method'])
                ->withCollection(OperationType::COLLECTION === $type)
                ->withCompositeIdentifier($resource->getCompositeIdentifier())
                ->withClass($resource->getClass())
                ->withPriority($priority++);

            foreach ($operation as $key => $value) {
                $newOperation = $this->setAttributeValue($newOperation, $key, $value);
            }

            $newOperation = $newOperation->withResource($resource);

            if ($newOperation->isCollection()) {
                $newOperation = $newOperation->withUriVariables([]);
            }

            $newOperation = $newOperation->withExtraProperties($newOperation->getExtraProperties() + ['is_legacy_resource_metadata' => true]);
            // Avoiding operation name collision by adding _collection, this is rewritten by the UriTemplateResourceMetadataCollectionFactory
            yield sprintf('%s%s', $newOperation->getRouteName() ?? $operationName, OperationType::COLLECTION === $type ? '_collection' : '') => $newOperation;
        }
    }

    /**
     * @param Operation|GraphQlOperation|ApiResource $operation
     * @param mixed                                  $value
     *
     * @return Operation|GraphQlOperation|ApiResource
     */
    private function setAttributeValue($operation, string $key, $value)
    {
        if ('identifiers' === $key) {
            if (!$operation instanceof ApiResource && $operation->isCollection()) {
                return $operation;
            }

            trigger_deprecation('api-platform/core', '2.7', 'The "identifiers" option is deprecated and will be renamed to "uriVariables".');
            if (\is_string($value)) {
                $value = [$value => [$operation->getClass(), $value]];
            }

            $uriVariables = [];
            foreach ($value ?? [] as $parameterName => $identifiedBy) {
                $uriVariables[$parameterName] = (new Link())->withFromClass($identifiedBy[0])->withIdentifiers([$identifiedBy[1]])->withParameterName($parameterName);
            }

            return $operation->withUriVariables($uriVariables);
        }

        [$camelCaseKey, $value] = $this->getKeyValue($key, $value);
        $methodName = 'with'.ucfirst($camelCaseKey);

        if (null === $value) {
            return $operation;
        }

        if (method_exists($operation, $methodName)) {
            return $operation->{$methodName}($value);
        }

        return $operation->withExtraProperties($operation->getExtraProperties() + [$key => $value]);
    }

    /**
     * @param ApiResource|Operation $resource
     *
     * @return ApiResource|Operation
     */
    private function identifiersToUriVariables(ResourceMetadata $resourceMetadata, $resource)
    {
        $identifiers = [];

        foreach ($this->propertyNameCollectionFactory->create($resource->getClass()) as $property) {
            $propertyMetadata = $this->propertyMetadataFactory->create($resource->getClass(), $property);
            if ($propertyMetadata->isIdentifier()) {
                $identifiers[] = $property;
            }
        }

        $compositeIdentifier = $resourceMetadata->getAttribute('composite_identifier', null);
        $numIdentifiers = \count($identifiers);
        if (null === $compositeIdentifier) {
            $compositeIdentifier = $numIdentifiers > 1 ? true : false;
        }

        if ($compositeIdentifier || 1 === $numIdentifiers) {
            $parameterName = 1 === $numIdentifiers ? $identifiers[0] : 'id';

            return $resource->withUriVariables([$parameterName => (new Link())->withFromClass($resource->getClass())->withIdentifiers($identifiers)->withParameterName($parameterName)->withCompositeIdentifier($compositeIdentifier)]);
        }

        $uriVariables = [];
        foreach ($identifiers as $identifier) {
            $uriVariables[$identifier] = (new Link())->withFromClass($resource->getClass())->withIdentifiers([$identifier])->withParameterName($identifier)->withCompositeIdentifier($compositeIdentifier);
        }

        return $resource->withUriVariables($uriVariables);
    }
}
