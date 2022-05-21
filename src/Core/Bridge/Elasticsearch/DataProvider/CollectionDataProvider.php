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

use ApiPlatform\Core\Bridge\Elasticsearch\Api\IdentifierExtractorInterface;
use ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\Extension\RequestBodySearchCollectionExtensionInterface as LegacyRequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination as LegacyPagination;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Exception\IndexNotFoundException;
use ApiPlatform\Elasticsearch\Exception\NonUniqueIdentifierException;
use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use ApiPlatform\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\Pagination\Pagination;
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
    private $identifierExtractor;
    private $denormalizer;
    private $pagination;
    private $resourceMetadataFactory;

    /**
     * @param LegacyRequestBodySearchCollectionExtensionInterface[]|RequestBodySearchCollectionExtensionInterface[] $collectionExtensions
     * @param ResourceMetadataFactoryInterface|ResourceMetadataCollectionFactoryInterface                           $resourceMetadataFactory
     * @param Pagination|LegacyPagination                                                                           $pagination
     */
    public function __construct(Client $client, DocumentMetadataFactoryInterface $documentMetadataFactory, IdentifierExtractorInterface $identifierExtractor = null, DenormalizerInterface $denormalizer, $pagination, $resourceMetadataFactory, iterable $collectionExtensions = [])
    {
        $this->client = $client;
        $this->documentMetadataFactory = $documentMetadataFactory;

        if ($this->identifierExtractor) {
            trigger_deprecation('api-platform', '2.7', sprintf('Passing an instance of "%s" is deprecated and will not be supported in 3.0.', IdentifierExtractorInterface::class));
        }

        $this->identifierExtractor = $identifierExtractor;
        $this->denormalizer = $denormalizer;
        $this->pagination = $pagination;

        if (!$resourceMetadataFactory instanceof ResourceMetadataCollectionFactoryInterface) {
            trigger_deprecation('api-platform/core', '2.7', sprintf('Use "%s" instead of "%s".', ResourceMetadataCollectionFactoryInterface::class, ResourceMetadataFactoryInterface::class));
        }

        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->collectionExtensions = $collectionExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        try {
            $resourceMetadata = $this->resourceMetadataFactory->create($resourceClass);
            if (false === $resourceMetadata->getCollectionOperationAttribute($operationName, 'elasticsearch', true, true)) {
                return false;
            }
        } catch (ResourceClassNotFoundException $e) {
            return false;
        }

        try {
            $this->documentMetadataFactory->create($resourceClass);
        } catch (IndexNotFoundException $e) {
            return false;
        }

        if ($this->identifierExtractor) {
            try {
                $this->identifierExtractor->getIdentifierFromResourceClass($resourceClass);
            } catch (NonUniqueIdentifierException $e) {
                return false;
            }
        } else {
            $operation = $context['operation'] ?? $this->resourceMetadataFactory->create($resourceClass)->getOperation($operationName);

            if (\count($operation->getIdentifiers()) > 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = []): iterable
    {
        $documentMetadata = $this->documentMetadataFactory->create($resourceClass);
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            if ($collectionExtension instanceof LegacyRequestBodySearchCollectionExtensionInterface) {
                $body = $collectionExtension->applyToCollection($body, $resourceClass, $operationName, $context);
            }

            if ($collectionExtension instanceof RequestBodySearchCollectionExtensionInterface) {
                $body = $collectionExtension->applyToCollection($body, $resourceClass, $context['operation'] ?? null, $context);
            }
        }

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body['query'] = ['match_all' => new \stdClass()];
        }

        $limit = $body['size'] = $body['size'] ?? $this->pagination->getLimit($resourceClass, $operationName, $context);
        $offset = $body['from'] = $body['from'] ?? $this->pagination->getOffset($resourceClass, $operationName, $context);

        $params = [
            'index' => $documentMetadata->getIndex(),
            'body' => $body,
        ];

        if (ElasticsearchVersion::supportsMappingType()) {
            $params['type'] = $documentMetadata->getType();
        }

        $documents = $this->client->search($params);

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
