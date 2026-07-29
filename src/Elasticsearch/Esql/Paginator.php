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

namespace ApiPlatform\Elasticsearch\Esql;

use ApiPlatform\Elasticsearch\Serializer\DocumentNormalizer;
use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Paginator for ES|QL results.
 *
 * ES|QL has no offset command and no total hit count: this paginator implements
 * partial pagination only. The next page is detected by fetching one extra row
 * (LIMIT itemsPerPage + 1) and discarding it, see {@see Extension\PaginationExtension}.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class Paginator implements \IteratorAggregate, PartialPaginatorInterface, HasNextPagePaginatorInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    private readonly array $documents;

    private readonly bool $hasNextPage;

    /**
     * @var array<string, object>
     */
    private array $cachedDenormalizedDocuments = [];

    /**
     * @param array{columns?: list<array{name: string, type: string}>, values?: list<list<mixed>>} $response the raw (row-oriented) ES|QL response
     */
    public function __construct(
        private readonly DenormalizerInterface $denormalizer,
        array $response,
        private readonly string $resourceClass,
        private readonly int $limit,
        private readonly int $currentPage = 1,
        private readonly array $denormalizationContext = [],
    ) {
        $columns = array_column($response['columns'] ?? [], 'name');
        $documents = array_map(static fn (array $row): array => self::rowToDocument($columns, $row), $response['values'] ?? []);

        $this->hasNextPage = $limit > 0 && \count($documents) > $limit;
        $this->documents = $this->hasNextPage ? \array_slice($documents, 0, $limit) : $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->documents);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return (float) $this->currentPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->limit;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        $denormalizationContext = array_merge([AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true], $this->denormalizationContext);

        foreach ($this->documents as $i => $document) {
            // ES|QL rows carry no index name, unlike search hits: the document "_id" alone
            // identifies a row, falling back to its position when the query does not select it.
            $cacheKey = isset($document['_id']) ? hash('xxh3', (string) $document['_id']) : "#$i";

            if (\array_key_exists($cacheKey, $this->cachedDenormalizedDocuments)) {
                yield $this->cachedDenormalizedDocuments[$cacheKey];

                continue;
            }

            yield $this->cachedDenormalizedDocuments[$cacheKey] = $this->denormalizer->denormalize(
                $document,
                $this->resourceClass,
                DocumentNormalizer::FORMAT,
                $denormalizationContext
            );
        }
    }

    /**
     * Converts an ES|QL row to the document shape expected by the DocumentNormalizer
     * (`_id` + `_source`), expanding dotted column names to nested arrays.
     *
     * @param list<string> $columns
     * @param list<mixed>  $row
     *
     * @return array<string, mixed>
     */
    private static function rowToDocument(array $columns, array $row): array
    {
        $document = ['_source' => []];

        foreach ($columns as $i => $name) {
            $value = $row[$i] ?? null;

            if (str_starts_with($name, '_')) {
                $document[$name] = $value;

                continue;
            }

            $segments = explode('.', $name);
            $leaf = array_pop($segments);
            $target = &$document['_source'];
            $skip = false;

            foreach ($segments as $segment) {
                if (!\array_key_exists($segment, $target)) {
                    $target[$segment] = [];
                } elseif (!\is_array($target[$segment])) {
                    // A "text" field mapped with a sub-field is returned by ES|QL as several
                    // columns sharing the same prefix (e.g. "title" and "title.keyword"),
                    // sorted alphabetically so the scalar parent always comes first. Sub-field
                    // columns only duplicate the parent value, so the already written scalar
                    // wins and the sub-column is skipped rather than descended into.
                    $skip = true;
                    break;
                }

                $target = &$target[$segment];
            }

            // First write wins: keeps the scalar value of a duplicated or multi-field column.
            if (!$skip && !\array_key_exists($leaf, $target)) {
                $target[$leaf] = $value;
            }

            unset($target);
        }

        return $document;
    }
}
