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

use ApiPlatform\Core\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Metadata\Operation;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class LinksResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = iterator_to_array($resource->getOperations());

            foreach ($operations as $operationName => $operation) {
                $operations[$operationName] = $operation->withLinks($this->getLinks($resourceMetadataCollection, $operationName, $operation));
            }


            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    private function getLinks(ResourceMetadataCollection $resourceMetadata, string $resourceOperationName, Operation $currentOperation): array
    {
        $links = [];

        $hasSameOperationLink = false;
        foreach ($resourceMetadata as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                if (!$operation->getRouteName() && Operation::METHOD_GET === $operation->getMethod()) {
                    if ($currentOperation->isCollection() === $operation->isCollection()) {
                        if (!$hasSameOperationLink) {
                            $hasSameOperationLink = true;
                            array_unshift($links, [$operationName, $operation->getIdentifiers(), $operation->getCompositeIdentifier(), $operation->isCollection()]);
                        }
                        continue;
                    }

                    array_push($links, [$operationName, $operation->getIdentifiers(), $operation->getCompositeIdentifier(), $operation->isCollection()]);
                }
            }
        }

        return $links;
    }
}
