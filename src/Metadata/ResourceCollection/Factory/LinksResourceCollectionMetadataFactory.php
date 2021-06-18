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
        $parentResourceMetadata = [];
        if ($this->decorated) {
            try {
                $parentResourceMetadata = $this->decorated->create($resourceClass);
            } catch (ResourceClassNotFoundException $resourceNotFoundException) {
                // Ignore not found exception from decorated factories
            }
        }

        foreach ($parentResourceMetadata as $i => $resource) {
            $operations = $resource->getOperations();

            foreach ($operations as $key => $operation) {
                if ($operation->isCollection()) {
                    $operations[$key] = $operation->withLinks($this->getLinks($parentResourceMetadata));
                }
            }

            $parentResourceMetadata[$i] = $resource->withOperations($operations);
        }

        return $parentResourceMetadata;
    }

    private function getLinks($resourceMetadata): array
    {
        // TODO: Check error here
        $links = [];

        foreach ($resourceMetadata as $resource) {
            foreach ($resource->getOperations() as $operationName => $operation) {
                // About the routeName we can't do the link as we don't know enough
                // TODO: find a better way to add links or not
                if (!$operation->getRouteName() && false === $operation->isCollection() && Operation::METHOD_GET === $operation->getMethod()) {
                    $links[] = $operationName;
                }
            }
        }

        return $links;
    }
}
