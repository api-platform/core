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

namespace ApiPlatform\Core\Bridge\Doctrine\PHPCR;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Doctrine\ODM\PHPCR\UnitOfWork;

/**
 * Decorates the Doctrine ODM paginator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Paginator implements \IteratorAggregate, PaginatorInterface
{
    /**
     * @var CommandCursor
     */
    private $cursor;
    /**
     * @var UnitOfWork
     */
    private $unitOfWork;
    /**
     * @var string
     */
    private $resourceClass;
    /**
     * @var int
     */
    private $firstResult;
    /**
     * @var int
     */
    private $maxResults;
    /**
     * @var int
     */
    private $totalItems;

    public function __construct(CommandCursor $cursor, UnitOfWork $unitOfWork, string $resourceClass)
    {
        $this->cursor = $cursor;
        $this->unitOfWork = $unitOfWork;
        $this->resourceClass = $resourceClass;

        $resultsFacetInfo = $this->getResultsFacetInfo();

        // See https://github.com/alcaeus/mongo-php-adapter#mongocommandcursor
        // Since the method getCursorInfo in CommandCursor always returns 0 for 'skip' and 'limit',
        // the values set in the facet stage are used instead.
        $this->firstResult = $resultsFacetInfo[0]['$skip'];
        $this->maxResults = $resultsFacetInfo[1]['$limit'];
        $this->totalItems = $cursor->toArray()[0]['count'][0]['count'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
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
    public function getIterator()
    {
        return new \ArrayIterator(array_map(function ($result) {
            return $this->unitOfWork->getOrCreateDocument($this->resourceClass, $result);
        }, $this->cursor->toArray()[0]['results']));
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return \count($this->getIterator());
    }

    private function getResultsFacetInfo(): array
    {
        $infoPipeline = $this->cursor->info()['query']['pipeline'];
        $indexFacetStage = 0;
        foreach ($infoPipeline as $indexStage => $infoStage) {
            if (array_key_exists('$facet', $infoStage)) {
                $indexFacetStage = $indexStage;
            }
        }

        return $infoPipeline[$indexFacetStage]['$facet']['results'];
    }
}
