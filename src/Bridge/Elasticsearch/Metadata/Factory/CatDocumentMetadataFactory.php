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

namespace ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Factory;

use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\DocumentMetadata;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\Common\Util\Inflector;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * Creates document's metadata using indices from the cat APIs.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/cat-indices.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CatDocumentMetadataFactory implements DocumentMetadataFactoryInterface
{
    private $client;
    private $resourceMetadataFactory;
    private $decorated;

    public function __construct(Client $client, ResourceMetadataFactoryInterface $resourceMetadataFactory, DocumentMetadataFactoryInterface $decorated = null)
    {
        $this->client = $client;
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

        if ($indexMetadata && null !== $indexMetadata->getIndex()) {
            return $indexMetadata;
        }

        $index = Inflector::tableize($this->resourceMetadataFactory->create($resourceClass)->getShortName());

        try {
            $this->client->cat()->indices(['index' => $index]);
        } catch (Missing404Exception $e) {
            if ($indexMetadata) {
                return $indexMetadata;
            }

            throw new IndexNotFoundException(sprintf('No index associated with the "%s" resource class.', $resourceClass));
        }

        if ($indexMetadata) {
            return $indexMetadata->withIndex($index);
        }

        return new DocumentMetadata($index);
    }
}
