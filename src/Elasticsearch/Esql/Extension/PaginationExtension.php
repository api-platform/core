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
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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
 * operation attribute to navigate pages. Requesting a "page" greater than 1
 * is rejected with a 400 response rather than silently ignored.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class PaginationExtension implements ResultCollectionExtensionInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly DenormalizerInterface $denormalizer,
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

        $this->assertPageIsNotRequested($context);

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
        if (null === $this->pagination) {
            throw new \LogicException(\sprintf('The pagination service is required to compute the result, did you forget to pass it to "%s"?', self::class));
        }

        $this->assertPageIsNotRequested($context);

        self::assertEsqlIsSupported($this->client);

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
            1,
            $context,
        );
    }

    /**
     * ES|QL has no offset command: the "page" parameter cannot be honored and must not be silently ignored.
     *
     * @param array<string, mixed> $context
     */
    private function assertPageIsNotRequested(array $context): void
    {
        if (null !== $this->pagination && 1 < $this->pagination->getPage($context)) {
            throw new InvalidArgumentException('ES|QL supports partial pagination only: the "page" parameter is not supported. Use keyset pagination instead, by sorting on a unique field and filtering it with a ComparisonFilter.');
        }
    }

    /**
     * The "esql" endpoint has been added in elasticsearch/elasticsearch 8.11, while
     * this component supports 8.4 and later for the Query DSL.
     */
    private static function assertEsqlIsSupported(object $client): void
    {
        if (!method_exists($client, 'esql')) {
            throw new \RuntimeException(\sprintf('ES|QL support requires elasticsearch/elasticsearch >= 8.11, but "%s" does not provide the "esql" endpoint.', $client::class));
        }
    }
}
