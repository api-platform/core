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

namespace ApiPlatform\Meilisearch\State;

use ApiPlatform\Meilisearch\Extension\RequestParametersCollectionExtensionInterface;
use ApiPlatform\Meilisearch\Paginator;
use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Collection provider for Meilisearch.
 *
 * Unlike Elasticsearch's CollectionProvider, the client type here is a
 * single concrete class (Meilisearch\Client) -- one official SDK, one
 * license, no elasticsearch-php-v7/elastic-elasticsearch-v8/opensearch-php
 * union type to juggle.
 *
 * @author API Platform Community
 */
final class CollectionProvider implements ProviderInterface
{
    /**
     * @param RequestParametersCollectionExtensionInterface[] $collectionExtensions
     */
    public function __construct(
        private readonly Client $client,
        private readonly ?DenormalizerInterface $denormalizer = null,
        private readonly ?Pagination $pagination = null,
        private readonly iterable $collectionExtensions = [],
        private readonly ?InflectorInterface $inflector = new Inflector(),
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
    {
        $resourceClass = $operation->getClass();
        $parameters = ['q' => ''];

        foreach ($this->collectionExtensions as $collectionExtension) {
            $parameters = $collectionExtension->applyToCollection($parameters, $resourceClass, $operation, $context);
        }

        $q = $parameters['q'] ?? '';
        unset($parameters['q']);

        $limit = $parameters['limit'] ??= $this->pagination->getLimit($operation, $context);
        $offset = $parameters['offset'] ??= $this->pagination->getOffset($operation, $context);

        $options = $operation->getStateOptions() instanceof Options ? $operation->getStateOptions() : new Options(index: $this->getIndex($operation));

        try {
            $searchResult = $this->client->index($options->getIndex() ?? $this->getIndex($operation))->search($q, $parameters);
        } catch (ApiException $e) {
            throw new Error(status: $e->httpStatus, detail: $e->getMessage(), title: $e->getMessage(), originalTrace: $e->getTrace());
        }

        return new Paginator(
            $this->denormalizer,
            $searchResult,
            $resourceClass,
            $limit,
            $offset,
            $context,
            $options->getPrimaryKey() ?? 'id',
        );
    }

    private function getIndex(Operation $operation): string
    {
        return $this->inflector->tableize($operation->getShortName());
    }
}
