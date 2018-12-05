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

namespace ApiPlatform\Core\Bridge\Elasticsearch\DataProvider;

use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\FullBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Core\Bridge\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use Elasticsearch\Client;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Collection data provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class CollectionDataProvider implements ContextAwareCollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private $client;
    private $documentMetadataFactory;
    private $denormalizer;
    private $pagination;
    private $collectionExtensions;

    /**
     * @param FullBodySearchCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, DenormalizerInterface $denormalizer, Pagination $pagination, iterable $collectionExtensions = [])
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;
        $this->denormalizer = $denormalizer;
        $this->pagination = $pagination;
        $this->collectionExtensions = $collectionExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        try {
            $this->documentMetadataFactory->create($resourceClass);
        } catch (IndexNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = [])
    {
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $body = $collectionExtension->applyToCollection($body, $resourceClass, $operationName, $context);
        }

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body = array_merge($body, [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ]);
        }

        $limit = $body['size'] ?? $this->pagination->getLimit($resourceClass, $operationName);
        $offset = $body['offset'] ?? $this->pagination->getOffset($resourceClass, $operationName);

        if (!isset($body['size'])) {
            $body = array_merge($body, ['size' => $limit]);
        }

        if (!isset($body['from'])) {
            $body = array_merge($body, ['from' => $offset]);
        }

        $documents = $this->client->search([
            'index' => $documentMetadata->getIndex(),
            'type' => $documentMetadata->getType(),
            'body' => $body,
        ]);

        return new Paginator(
            $this->denormalizer,
            $documents,
            $resourceClass,
            $limit,
            $offset
        );
    }
}
