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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Elasticsearch\Client as LegacyClient;
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
    public function __construct(private readonly LegacyClient|Client $client, private readonly ?DocumentMetadataFactoryInterface $documentMetadataFactory = null, private readonly ?DenormalizerInterface $denormalizer = null, private readonly ?Pagination $pagination = null, private readonly iterable $collectionExtensions = []) // @phpstan-ignore-line
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
        if ($this->documentMetadataFactory && $operation->getElasticsearch() && !$operation->getStateOptions()) {
            $options = $this->convertDocumentMetadata($this->documentMetadataFactory->create($resourceClass));
        }

        $params = [
            'index' => $options->getIndex() ?? $this->getIndex($operation),
            'body' => $body,
        ];

        try {
            $documents = $this->client->search($params); // @phpstan-ignore-line
        } catch (ClientResponseException $e) {
            $response = $e->getResponse();
            throw new Error(status: $response->getStatusCode(), detail: (string) $response->getBody(), title: $response->getReasonPhrase(), originalTrace: $e->getTrace());
        }

        if ($documents instanceof Elasticsearch) {
            $documents = $documents->asArray();
        }

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
