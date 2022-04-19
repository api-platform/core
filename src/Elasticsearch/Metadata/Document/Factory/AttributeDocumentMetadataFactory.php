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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Creates document's metadata using the attribute configuration.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class AttributeDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    /**
     * @var ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface
     */
    private $resourceMetadataFactory;
    private $decorated;

    /**
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory
     */
    public function __construct($resourceMetadataFactory, ?DocumentMetadataFactoryInterface $decorated = null)
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
            /** @var ResourceMetadata|ResourceMetadataCollection */
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);

            $index = $resourceMetadata instanceof ResourceMetadata ? $resourceMetadata->getAttribute('elasticsearch_index') : ($resourceMetadata->getOperation()->getExtraProperties()['elasticsearch_index'] ?? null);

            if (null !== $index) {
                $documentMetadata = $documentMetadata ? $documentMetadata->withIndex($index) : new DocumentMetadata($index);
            }
        }

        if (!$documentMetadata || DocumentMetadata::DEFAULT_TYPE === $documentMetadata->getType()) {
            /** @var ResourceMetadata|ResourceMetadataCollection */
            $resourceMetadata = $resourceMetadata ?? $this->resourceMetadataFactory->create($resourceClass);
            $type = $resourceMetadata instanceof ResourceMetadata ? $resourceMetadata->getAttribute('elasticsearch_type') : ($resourceMetadata->getOperation()->getExtraProperties()['elasticsearch_type'] ?? null);

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

class_alias(AttributeDocumentMetadataFactory::class, \ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\AttributeDocumentMetadataFactory::class);
