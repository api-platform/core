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

use ApiPlatform\Elasticsearch\Extension\RequestBodySearchCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Metadata\Document\DocumentMetadata;
use ApiPlatform\Elasticsearch\Metadata\Document\Factory\DocumentMetadataFactoryInterface;
use ApiPlatform\Elasticsearch\Paginator;
use ApiPlatform\Elasticsearch\Util\ElasticsearchVersion;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Util\Inflector;
use Elasticsearch\Client;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Collection provider for Elasticsearch.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CollectionProvider implements ProviderInterface
{
    /**
     * @param RequestBodySearchCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(private readonly Client $client, private readonly DocumentMetadataFactoryInterface $documentMetadataFactory, private readonly DenormalizerInterface $denormalizer, private readonly Pagination $pagination, private readonly iterable $collectionExtensions = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
    {
        $resourceClass = $operation->getClass();
        $body = [];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $body = $collectionExtension->applyToCollection($body, $resourceClass, $operation, $context);
        }

        if (!isset($body['query']) && !isset($body['aggs'])) {
            $body['query'] = ['match_all' => new \stdClass()];
        }

        $limit = $body['size'] ??= $this->pagination->getLimit($operation, $context);
        $offset = $body['from'] ??= $this->pagination->getOffset($operation, $context);

        $options = $operation->getStateOptions() instanceof Options ? $operation->getStateOptions() : new Options(index: $this->getIndex($operation));

        // TODO: remove in 4.x
        if ($operation->getElasticsearch() && !$operation->getStateOptions()) {
            $options = $this->convertDocumentMetadata($this->documentMetadataFactory->create($resourceClass));
        }

        $params = [
            'index' => $options->getIndex() ?? $this->getIndex($operation),
            'body' => $body,
        ];

        if (null !== $options->getType() && ElasticsearchVersion::supportsMappingType()) {
            $params['type'] = $options->getType();
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

    private function convertDocumentMetadata(DocumentMetadata $documentMetadata): Options
    {
        return new Options($documentMetadata->getIndex(), $documentMetadata->getType());
    }

    private function getIndex(Operation $operation): string
    {
        return Inflector::tableize($operation->getShortName());
    }
}
