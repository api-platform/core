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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Adds a {@see NotExposed} operation with {@see NotFoundAction} on a resource which only has a GetCollection.
 * This operation helps to generate resource IRI for items.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 * @experimental
 */
final class NotExposedOperationResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    private $linkFactory;
    private $decorated;

    public function __construct(LinkFactoryInterface $linkFactory, ?ResourceMetadataCollectionFactoryInterface $decorated = null)
    {
        $this->linkFactory = $linkFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = new ResourceMetadataCollection($resourceClass);
        if ($this->decorated) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
        }

        // Do not add a NotExposed operation on a resourceClass with no resource
        if (!$resourceMetadataCollection->count()) {
            return $resourceMetadataCollection;
        }

        foreach ($resourceMetadataCollection as $resource) {
            /** @var ApiResource $resource */
            $operations = $resource->getOperations();

            foreach ($operations as $operation) {
                // Ignore collection and GraphQL operations
                if ($operation instanceof CollectionOperationInterface || $operation instanceof GraphQlOperation) {
                    continue;
                }

                // An item operation has been found, nothing to do anymore in this factory
                return $resourceMetadataCollection;
            }
        }

        // No item operation has been found on all resources for resource class: generate one on the last resource
        // Helpful to generate an IRI for a resource without declaring the Get operation
        // @phpstan-ignore-next-line
        $operation = (new NotExposed())->withResource($resource)->withUriTemplate(null); // force uriTemplate to null to don't inherit it from resource
        if (!$this->linkFactory->createLinksFromIdentifiers($resource)) { // @phpstan-ignore-line
            $operation = $operation->withRouteName('api_genid');
        }
        $operations->add(sprintf('_api_%s_get', $resource->getShortName()), $operation)->sort(); // @phpstan-ignore-line

        return $resourceMetadataCollection;
    }
}
