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

namespace ApiPlatform\Meilisearch;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Meilisearch\Search\SearchResult;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Paginator for Meilisearch. Assumes offset/limit pagination (the SearchResult
 * was requested without `page`/`hitsPerPage`, see CollectionProvider) so
 * getEstimatedTotalHits() is always populated.
 *
 * @author API Platform Community
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface
{
    private array $cachedDenormalizedHits = [];

    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        private readonly SearchResult $searchResult,
        private readonly string $resourceClass,
        private readonly int $limit,
        private readonly int $offset,
        private readonly array $denormalizationContext = [],
        private readonly string $primaryKey = 'id',
    ) {
    }

    public function count(): int
    {
        return $this->searchResult->getHitsCount();
    }

    public function getLastPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->limit) ?: 1.;
    }

    public function getTotalItems(): float
    {
        return (float) ($this->searchResult->getEstimatedTotalHits() ?? $this->searchResult->getHitsCount());
    }

    public function getCurrentPage(): float
    {
        if (0 >= $this->limit) {
            return 1.;
        }

        return floor($this->offset / $this->limit) + 1.;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->limit;
    }

    public function getIterator(): \Traversable
    {
        $denormalizationContext = array_merge([AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true], $this->denormalizationContext);

        foreach ($this->searchResult->getHits() as $hit) {
            $cacheKey = isset($hit[$this->primaryKey]) ? hash('xxh3', (string) $hit[$this->primaryKey]) : null;

            if ($cacheKey && \array_key_exists($cacheKey, $this->cachedDenormalizedHits)) {
                yield $this->cachedDenormalizedHits[$cacheKey];
                continue;
            }

            $object = $this->denormalizer->denormalize($hit, $this->resourceClass, 'array', $denormalizationContext);

            if ($cacheKey) {
                $this->cachedDenormalizedHits[$cacheKey] = $object;
            }

            yield $object;
        }
    }
}
