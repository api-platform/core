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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;

/**
 * Creates document's metadata using the attribute configuration.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class AttributeDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private $resourceMetadataFactory;
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $resourceMetadataFactory, ?DocumentMetadataFactoryInterface $decorated = null)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): DocumentMetadata
    {
        $indexMetadata = null;

        if ($this->decorated) {
            try {
                $indexMetadata = $this->decorated->create($resourceClass);
            } catch (IndexNotFoundException $e) {
            }
        }

        $resourceMetadata = null;

        if (!$indexMetadata || null === $indexMetadata->getIndex()) {
            $resourceMetadata = $resourceMetadata ?? $this->resourceMetadataFactory->create($resourceClass);

            if (null !== $index = $resourceMetadata->getAttribute('elasticsearch_index')) {
                $indexMetadata = $indexMetadata ? $indexMetadata->withIndex($index) : new DocumentMetadata($index);
            }
        }

        if (!$indexMetadata || DocumentMetadata::DEFAULT_TYPE === $indexMetadata->getType()) {
            $resourceMetadata = $resourceMetadata ?? $this->resourceMetadataFactory->create($resourceClass);

            if (null !== $type = $resourceMetadata->getAttribute('elasticsearch_type')) {
                $indexMetadata = $indexMetadata ? $indexMetadata->withType($type) : new DocumentMetadata(null, $type);
            }
        }

        if ($indexMetadata) {
            return $indexMetadata;
        }

        throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
    }
}
