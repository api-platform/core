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
use ApiPlatform\Metadata\Util\Inflector;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * Creates document's metadata using indices from the cat APIs.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/cat-indices.html
 * @deprecated
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CatDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    // @phpstan-ignore-next-line
    public function __construct(private readonly Client $client, private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory, private readonly ?DocumentMetadataFactoryInterface $decorated = null)
    {
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
            } catch (IndexNotFoundException) {
            }
        }

        if ($documentMetadata && null !== $documentMetadata->getIndex()) {
            return $documentMetadata;
        }

        $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
        $resourceShortName = $resourceMetadata->getOperation()->getShortName();

        if (null === $resourceShortName) {
            return $this->handleNotFound($documentMetadata, $resourceClass);
        }

        $index = Inflector::tableize($resourceShortName);

        try {
            // @phpstan-ignore-next-line
            $this->client->cat()->indices(['index' => $index]);
            // @phpstan-ignore-next-line
        } catch (Missing404Exception|ClientResponseException) {
            return $this->handleNotFound($documentMetadata, $resourceClass);
        }

        return ($documentMetadata ?? new DocumentMetadata())->withIndex($index);
    }

    /**
     * @throws IndexNotFoundException
     */
    private function handleNotFound(?DocumentMetadata $documentMetadata, string $resourceClass): DocumentMetadata
    {
        if ($documentMetadata) {
            return $documentMetadata;
        }

        throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
    }
}
