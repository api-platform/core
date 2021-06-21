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
use ApiPlatform\Core\Metadata\ResourceCollection\ResourceCollection;
use ApiPlatform\Metadata\Operation;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 * @experimental
 */
final class LinksResourceCollectionMetadataFactory implements ResourceCollectionMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceCollectionMetadataFactoryInterface $decorated = null)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceCollection
    {
        $resourceMetadataCollection = new ResourceCollection();
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        foreach ($resourceMetadataCollection as $i => $resource) {
            $operations = iterator_to_array($resource->getOperations());

            foreach ($operations as $key => $operation) {
                $operations[$key] = $operation->withLinks($this->getLinks($resourceMetadataCollection));
            }

            $resourceMetadataCollection[$i] = $resource->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    private function getLinks($resourceMetadata): array
    {
        $links = [];

        foreach ($resourceMetadata as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                if (!$operation->getRouteName() && false === $operation->isCollection() && Operation::METHOD_GET === $operation->getMethod()) {
                    $links[] = [$operationName, $operation->getIdentifiers()];
                }
            }
        }

        return $links;
    }
}
