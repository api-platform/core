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

namespace ApiPlatform\Doctrine\Odm;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * Decorates the Doctrine MongoDB ODM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface, HasNextPagePaginatorInterface
{
    private readonly \ArrayIterator $iterator;

    private readonly int $firstResult;

    private readonly int $maxResults;

    private readonly int $totalItems;

    private readonly int $count;

    public function __construct(Iterator $mongoDbOdmIterator, UnitOfWork $unitOfWork, string $resourceClass)
    {
        $result = $mongoDbOdmIterator->toArray()[0];

        if (array_diff_key(['results' => 1, 'count' => 1, '__api_first_result__' => 1, '__api_max_results__' => 1], $result)) {
            throw new RuntimeException('The result of the query must contain only "__api_first_result__", "__api_max_results__", "results" and "count" fields.');
        }

        // The "count" facet contains the total number of documents,
        // it is not set when the query does not return any document
        $this->totalItems = $result['count'][0]['count'] ?? 0;

        // The "results" facet contains the returned documents
        if ([] === $result['results']) {
            $this->count = 0;
            $this->iterator = new \ArrayIterator();
        } else {
            $this->count = \count($result['results']);
            $this->iterator = new \ArrayIterator(array_map(
                static fn ($result): object => $unitOfWork->getOrCreateDocument($resourceClass, $result),
                $result['results'],
            ));
        }

        $this->firstResult = $result['__api_first_result__'];
        $this->maxResults = $result['__api_max_results__'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return ceil($this->totalItems / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNextPage(): bool
    {
        return $this->getLastPage() > $this->getCurrentPage();
    }
}
