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

namespace ApiPlatform\Elasticsearch\Esql\Extension;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Elasticsearch\Esql\Paginator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\Pagination\Pagination;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Paginates the ES|QL query and executes it.
 *
 * ES|QL supports no offset: only partial (cursor-friendly) pagination is
 * available. One extra row is fetched (LIMIT itemsPerPage + 1) to detect
 * whether a next page exists; combine with a sorted unique field and a
 * ComparisonFilter (keyset pagination) or the "paginationViaCursor"
 * operation attribute to navigate pages.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class PaginationExtension implements ResultCollectionExtensionInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly ?DenormalizerInterface $denormalizer = null,
        private readonly ?Pagination $pagination = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applyToCollection(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        if (null === $this->pagination || !$this->pagination->isEnabled($operation, $context)) {
            return;
        }

        $limit = $this->pagination->getLimit($operation, $context);

        // one extra row to detect whether a next page exists
        $query->limit($limit > 0 ? $limit + 1 : 0);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool
    {
        return null !== $this->pagination && $this->pagination->isEnabled($operation, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): iterable
    {
        try {
            $response = $this->client->esql()->query(['body' => $query->compile()]);
        } catch (ClientResponseException $e) {
            $errorResponse = $e->getResponse();
            throw new Error(status: $errorResponse->getStatusCode(), detail: (string) $errorResponse->getBody(), title: $errorResponse->getReasonPhrase(), originalTrace: $e->getTrace());
        }

        if ($response instanceof Elasticsearch) {
            $response = $response->asArray();
        }

        $limit = $this->pagination->getLimit($operation, $context);

        return new Paginator(
            $this->denormalizer,
            $response,
            $resourceClass,
            $limit,
            $this->pagination->getPage($context),
            $context,
        );
    }
}
