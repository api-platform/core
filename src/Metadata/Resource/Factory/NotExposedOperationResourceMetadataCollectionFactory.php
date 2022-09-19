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
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\Symfony\Routing\SkolemIriConverter;

/**
 * Adds a {@see NotExposed} operation with {@see NotFoundAction} on a resource which only has a GetCollection.
 * This operation helps to generate resource IRI for items.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
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

        /** @var ApiResource $resource */
        foreach ($resourceMetadataCollection as $resource) {
            $operations = $resource->getOperations();

            foreach ($operations as $operation) {
                // An item operation has been found, nothing to do anymore in this factory
                if ((HttpOperation::METHOD_GET === $operation->getMethod() && !$operation instanceof CollectionOperationInterface) || ($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false)) {
                    return $resourceMetadataCollection;
                }
            }
        }

        // No item operation has been found on all resources for resource class: generate one on the last resource
        // Helpful to generate an IRI for a resource without declaring the Get operation
        /** @var HttpOperation $operation */
        $operation = (new NotExposed())->withClass($resource->getClass())->withShortName($resource->getShortName()); // @phpstan-ignore-line $resource is defined if count > 0

        if (!$this->linkFactory->createLinksFromIdentifiers($operation)) {
            $operation = $operation->withUriTemplate(SkolemIriConverter::$skolemUriTemplate);
        }

        $operations->add(sprintf('_api_%s_get', $operation->getShortName()), $operation)->sort(); // @phpstan-ignore-line $operation exists

        return $resourceMetadataCollection;
    }
}
