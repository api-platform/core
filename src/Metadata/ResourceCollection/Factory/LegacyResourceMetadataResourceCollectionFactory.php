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
use ApiPlatform\Core\Metadata\ResourceCollection\DeprecationMetadataTrait;
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\CustomActionDummy;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource;

final class LegacyResourceMetadataResourceCollectionFactory implements ResourceCollectionMetadataFactoryInterface
{
    use DeprecationMetadataTrait;
    private $decorated;
    private $resourceMetadataFactory;
    private $defaults;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null, ResourceMetadataFactoryInterface $resourceMetadataFactory = null, array $defaults = [])
    {
        $this->decorated = $decorated;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->defaults = $defaults + ['attributes' => []];
    }

    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadataCollection = new ResourceCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        } catch (ResourceClassNotFoundException $resourceNotFoundException) {
            return $resourceMetadataCollection;
        }

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

        foreach ($attributes as $key => $value) {
            [$key, $value] = $this->getKeyValue($key, $value);
            $resource = $resource->{'with'.ucfirst($key)}($value);
        }

        $operations = [];
        foreach ($this->createOperations($resourceMetadata->getItemOperations(), OperationType::ITEM) as $operationName => $operation) {
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName());
        }

        foreach ($this->createOperations($resourceMetadata->getCollectionOperations(), OperationType::COLLECTION) as $operationName => $operation) {
            $operations[$operationName] = $operation->withShortName($resourceMetadata->getShortName());
        }

        $resourceMetadataCollection[] = $resource
            ->withShortName($resourceMetadata->getShortName())
            ->withDescription($resourceMetadata->getDescription())
            ->withClass($resourceClass)
            ->withTypes([$resourceMetadata->getIri()])
            ->withOperations($operations);
            // ->withGraphql($resourceMetadata->getGraphql()); // TODO: fix this with graphql

        return $resourceMetadataCollection;
    }

    private function createOperations(array $operations, string $type): iterable
    {
        $priority = 0;
        foreach ($operations as $operationName => $operation) {
            $newOperation = new Operation(method: $operation['method'], collection: OperationType::COLLECTION === $type, priority: $priority++);
            foreach ($operation as $key => $value) {
                [$key, $value] = $this->getKeyValue($key, $value);
                $newOperation = $newOperation->{'with'.ucfirst($key)}($value);
            }

            // Avoiding operation name collision by adding _collection, this is rewritten by the UriTemplateResourceCollectionMetadataFactory
            yield sprintf('%s%s', $newOperation->getRouteName() ?? $operationName, OperationType::COLLECTION === $type ? '_collection' : '') => $newOperation;
        }
    }
}
