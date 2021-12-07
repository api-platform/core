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

namespace ApiPlatform\Elasticsearch\State;

use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Paginator;
use ApiPlatform\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Elasticsearch\Client;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Collection provider for Elasticsearch.
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CollectionProvider implements ProviderInterface
{
    private $client;
    private $documentMetadataFactory;
    private $denormalizer;
    private $pagination;
    private $resourceMetadataCollectionFactory;

    /**
     * @param RequestBodySearchCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, DenormalizerInterface $denormalizer, Pagination $pagination, ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory, iterable $collectionExtensions = [])
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;

        $this->denormalizer = $denormalizer;
        $this->pagination = $pagination;

        $this->resourceMetadataCollectionFactory = $resourceMetadataCollectionFactory;
        $this->collectionExtensions = $collectionExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = []): bool
    {
        try {
            $resourceMetadata = $this->resourceMetadataCollectionFactory->create($resourceClass);
            $operation = $context['operation'] ?? $resourceMetadata->getOperation($operationName, true);
            if (false === $operation->getElasticsearch() || (null !== ($uriVariables = $operation->getUriVariables()) && \count($uriVariables) > 1)) {
                return false;
            }
        } catch (OperationNotFoundException $e) {
            return false;
        }

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
    public function provide(string $resourceClass, array $identifiers = [], ?string $operationName = null, array $context = [])
    {
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $body = $collectionExtension->applyToCollection($body, $resourceClass, $operationName, $context);
        }

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body['query'] = ['match_all' => new \stdClass()];
        }

        $limit = $body['size'] = $body['size'] ?? $this->pagination->getLimit($resourceClass, $operationName, $context);
        $offset = $body['from'] = $body['from'] ?? $this->pagination->getOffset($resourceClass, $operationName, $context);

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
            $offset,
            $context
        );
    }

    private $collectionExtensions;
}
