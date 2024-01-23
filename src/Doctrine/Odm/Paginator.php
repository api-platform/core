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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
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
    public const LIMIT_ZERO_MARKER_FIELD = '___';
    public const LIMIT_ZERO_MARKER = 'limit0';

    private ?\ArrayIterator $iterator = null;

    private readonly int $firstResult;

    private readonly int $maxResults;

    private readonly int $totalItems;

    public function __construct(private readonly Iterator $mongoDbOdmIterator, private readonly UnitOfWork $unitOfWork, private readonly string $resourceClass, private readonly array $pipeline)
    {
        $resultsFacetInfo = $this->getFacetInfo('results');
        $this->getFacetInfo('count');

        /*
         * Since the {@see \MongoDB\Driver\Cursor} class does not expose information about
         * skip/limit parameters of the query, the values set in the facet stage are used instead.
         */
        $this->firstResult = $this->getStageInfo($resultsFacetInfo, '$skip');
        $this->maxResults = $this->hasLimitZeroStage($resultsFacetInfo) ? 0 : $this->getStageInfo($resultsFacetInfo, '$limit');
        $this->totalItems = $mongoDbOdmIterator->toArray()[0]['count'][0]['count'] ?? 0;
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
        return $this->iterator ?? $this->iterator = new \ArrayIterator(array_map(fn ($result): object => $this->unitOfWork->getOrCreateDocument($this->resourceClass, $result), $this->mongoDbOdmIterator->toArray()[0]['results']));
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return is_countable($this->mongoDbOdmIterator->toArray()[0]['results']) ? \count($this->mongoDbOdmIterator->toArray()[0]['results']) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function hasNextPage(): bool
    {
        return $this->getLastPage() > $this->getCurrentPage();
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getFacetInfo(string $field): array
    {
        foreach ($this->pipeline as $indexStage => $infoStage) {
            if (\array_key_exists('$facet', $infoStage)) {
                if (!isset($this->pipeline[$indexStage]['$facet'][$field])) {
                    throw new InvalidArgumentException("\"$field\" facet was not applied to the aggregation pipeline.");
                }

                return $this->pipeline[$indexStage]['$facet'][$field];
            }
        }

        throw new InvalidArgumentException('$facet stage was not applied to the aggregation pipeline.');
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getStageInfo(array $resultsFacetInfo, string $stage): int
    {
        foreach ($resultsFacetInfo as $resultFacetInfo) {
            if (isset($resultFacetInfo[$stage])) {
                return $resultFacetInfo[$stage];
            }
        }

        throw new InvalidArgumentException("$stage stage was not applied to the facet stage of the aggregation pipeline.");
    }

    private function hasLimitZeroStage(array $resultsFacetInfo): bool
    {
        foreach ($resultsFacetInfo as $resultFacetInfo) {
            if (self::LIMIT_ZERO_MARKER === ($resultFacetInfo['$match'][self::LIMIT_ZERO_MARKER_FIELD] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
