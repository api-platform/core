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

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

final class ResourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;
    private $resourceMetadataFactory;
    private $defaults;
    private $converter;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
        $this->converter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $parentResourceCollection = null;
        if ($this->decorated) {
            try {
                $parentResourceCollection = $this->decorated->create($resourceClass);
                if ($parentResourceCollection[0] ?? false) {
                    return $parentResourceCollection;
                }
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            }
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attributes = $resourceMetadata->getAttributes() ?? [];

        if ($attributes && $this->defaults['attributes']) {
            foreach ($attributes as $key => $value) {
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        $resource = new Resource();

        foreach ($attributes as $key => $value) {
            $camelCaseKey = $this->converter->denormalize($key);
            $value = $this->sanitizeValueFromKey($key, $value);
            $resource->{$camelCaseKey} = $value;
        }

        $resource->operations = [];

        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM) as $operationName => $operation) {
            $operation->shortName = $resourceMetadata->getShortName();
            $resource->operations[$operationName] = $operation;
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION) as $operationName => $operation) {
            $operation->shortName = $resourceMetadata->getShortName();
            $resource->operations[$operationName] = $operation;
        }

        $resource->shortName = $resourceMetadata->getShortName();
        $resource->description = $resourceMetadata->getDescription();
        $resource->class = $resourceClass;
        $resource->types = [$resourceMetadata->getIri()];
        $resource->graphQl = $resourceMetadata->getGraphql(); // TODO: fix this with graphql

        return new ResourceCollection([$resource]);
    }

    private function createOperations(array $operations, string $type): iterable
    {
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method']);

            if (isset($operation['path'])) {
                $newOperation->uriTemplate = $operation['path'];
                unset($operation['path']);
            }

            foreach ($operation as $operationKey => $operationValue) {
                $camelCaseKey = $this->converter->denormalize($operationKey);

                $operationValue = $this->sanitizeValueFromKey($operationKey, $operationValue);

                $newOperation->{$camelCaseKey} = $operationValue;
            }

            // Avoiding operation name collision by adding _collection, this is rewritten by the UriTemplateResourceCollectionMetadataFactory
            yield sprintf('%s%s', $operationName, OperationType::COLLECTION === $type && 'get' === $operationName ? '_collection' : '') => $newOperation;
        }
    }

    public function sanitizeValueFromKey(string $key, $value)
    {
        return \in_array($key, ['identifiers', 'validation_groups'], true) ? [$value] : $value;
    }
}
