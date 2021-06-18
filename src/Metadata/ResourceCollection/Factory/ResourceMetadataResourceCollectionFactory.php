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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

final class ResourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $resourceMetadataFactory;
    private $defaults;
    private $converter;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
        $this->converter = new CamelCaseToSnakeCaseNameConverter();
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $attributes = $resourceMetadata->getAttributes() ?? [];

        if ($attributes && $this->defaults['attributes']) {
            foreach ($attributes as $key => $value) {
                if (!$value) { // When value is null, take the new default
                    unset($attributes[$key]);
                    continue;
                }
                if (!isset($attributes[$key])) {
                    $attributes[$key] = $value;
                }
            }
        }

        $resource = (new Resource())->withOperations([]);

        if (isset($attributes['access_control'])) { // Manage deprecated accessControl attribute
            // TODO: throw deprecation
            $attributes['security'] = $attributes['access_control'];
            unset($attributes['access_control']);
        }

        foreach ($attributes as $key => $value) {
            $camelCaseKey = $this->converter->denormalize($key);
            $value = $this->sanitizeValueFromKey($key, $value);
            $resource = $resource->{'with'.ucfirst($camelCaseKey)}($value);
        }

        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM) as $operationName => $operation) {
            $operation = $operation->withShortName($resourceMetadata->getShortName());
            $operations = iterator_to_array($resource->getOperations());
            $operations[$operationName] = $operation;
            $resource = $resource->withOperations($operations);
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION) as $operationName => $operation) {
            $operation = $operation->withShortName($resourceMetadata->getShortName());
            $operations = iterator_to_array($resource->getOperations());
            $operations[$operationName] = $operation;
            $resource = $resource->withOperations($operations);
        }

        $resource = $resource
            ->withShortName($resourceMetadata->getShortName())
            ->withDescription($resourceMetadata->getDescription())
            ->withClass($resourceClass)
            ->withTypes([$resourceMetadata->getIri()]);
        // $resource = $resource->withGraphql($resourceMetadata->getGraphql()); // TODO: fix this with graphql

        return new ResourceCollection([$resource]);
    }

    private function createOperations(array $operations, string $type): iterable
    {
        $priority = 0;
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method'], collection: OperationType::COLLECTION === $type, priority: $priority++);

            if (isset($operation['path'])) {
                $newOperation = $newOperation->withUriTemplate($operation['path']);
                unset($operation['path']);
            }

            if (isset($operation['access_control'])) { // Manage deprecated accessControl attribute
                // TODO: throw deprecation
                $newOperation = $newOperation->withSecurity($operation['access_control']);
                unset($operation['access_control']);
            }

            foreach ($operation as $operationKey => $operationValue) {
                $camelCaseKey = $this->converter->denormalize($operationKey);
                $operationValue = $this->sanitizeValueFromKey($operationKey, $operationValue);
                $newOperation = $newOperation->{'with'.ucfirst($camelCaseKey)}($operationValue);
            }

            // Avoiding operation name collision by adding _collection, this is rewritten by the UriTemplateResourceCollectionMetadataFactory
            yield sprintf('%s%s', $newOperation->getRouteName() ?? $operationName, OperationType::COLLECTION === $type ? '_collection' : '') => $newOperation;
        }
    }

    public function sanitizeValueFromKey(string $key, $value)
    {
        return \in_array($key, ['identifiers', 'validation_groups'], true) ? [$value] : $value;
    }
}
