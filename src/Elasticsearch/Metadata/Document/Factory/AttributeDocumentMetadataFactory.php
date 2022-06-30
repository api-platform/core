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

namespace ApiPlatform\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

/**
 * Creates document's metadata using the attribute configuration.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class AttributeDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory;
    private ?DocumentMetadataFactoryInterface $decorated;

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, ?DocumentMetadataFactoryInterface $decorated = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): DocumentMetadata
    {
        $documentMetadata = null;

        if ($this->decorated) {
            try {
                $documentMetadata = $this->decorated->create($resourceClass);
            } catch (IndexNotFoundException $e) {
            }
        }

        $resourceMetadata = null;

        if (!$documentMetadata || null === $documentMetadata->getIndex()) {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $index = $resourceMetadata->getOperation()->getExtraProperties()['elasticsearch_index'] ?? null;

            if (null !== $index) {
                $documentMetadata = $documentMetadata ? $documentMetadata->withIndex($index) : new DocumentMetadata($index);
            }
        }

        if (!$documentMetadata || DocumentMetadata::DEFAULT_TYPE === $documentMetadata->getType()) {
            $resourceMetadata = $resourceMetadata ?? $this->resourceMetadataFactory->create($resourceClass);
            $type = $resourceMetadata->getOperation()->getExtraProperties()['elasticsearch_type'] ?? null;

            if (null !== $type) {
                $documentMetadata = $documentMetadata ? $documentMetadata->withType($type) : new DocumentMetadata(null, $type);
            }
        }

        if ($documentMetadata) {
            return $documentMetadata;
        }

        throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
    }
}
