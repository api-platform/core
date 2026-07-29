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

namespace ApiPlatform\Elasticsearch\Esql\State;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Extension\CollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Esql\Extension\ResultCollectionExtensionInterface;
use ApiPlatform\Elasticsearch\Esql\Paginator;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Metadata\InflectorInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\Inflector;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Collection provider using ES|QL (the Elasticsearch "_query" endpoint).
 *
 * Requires Elasticsearch >= 8.14. Note that ES|QL comes with limitations
 * compared to the Query DSL: no offset (partial pagination only), no total
 * item count and no support for "nested" fields.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class CollectionProvider implements ProviderInterface
{
    /**
     * @param iterable<CollectionExtensionInterface> $collectionExtensions
     */
    public function __construct(
        private readonly Client $client,
        private readonly ?DenormalizerInterface $denormalizer = null,
        private readonly iterable $collectionExtensions = [],
        private readonly ?InflectorInterface $inflector = new Inflector(),
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $resourceClass = $operation->getClass();
        $options = $operation->getStateOptions() instanceof Options ? $operation->getStateOptions() : new Options();

        $query = new EsqlQuery($options->getIndex() ?? $this->inflector->tableize($operation->getShortName()));

        if (\is_callable($handleLinks = $options->getHandleLinks())) {
            $handleLinks($query, $uriVariables, ['operation' => $operation, 'resourceClass' => $resourceClass] + $context);
        }

        foreach ($this->collectionExtensions as $extension) {
            $extension->applyToCollection($query, $resourceClass, $operation, $context);

            if ($extension instanceof ResultCollectionExtensionInterface && $extension->supportsResult($resourceClass, $operation, $context)) {
                return $extension->getResult($query, $resourceClass, $operation, $context);
            }
        }

        try {
            $response = $this->client->esql()->query(['body' => $query->compile()]);
        } catch (ClientResponseException $e) {
            $errorResponse = $e->getResponse();
            throw new Error(status: $errorResponse->getStatusCode(), detail: (string) $errorResponse->getBody(), title: $errorResponse->getReasonPhrase(), originalTrace: $e->getTrace());
        }

        if ($response instanceof Elasticsearch) {
            $response = $response->asArray();
        }

        return new Paginator(
            $this->denormalizer,
            $response,
            $resourceClass,
            $query->getLimit() ?? 0,
            1,
            $context,
        );
    }
}
