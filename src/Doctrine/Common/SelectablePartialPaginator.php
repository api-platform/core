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

namespace ApiPlatform\Doctrine\Common;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\Common\Collections\Selectable;

/**
 * @template T of object
 *
 * @implements PartialPaginatorInterface<T>
 * @implements \IteratorAggregate<T>
 */
final class SelectablePartialPaginator implements \IteratorAggregate, PartialPaginatorInterface
{
    /**
     * @var ReadableCollection<array-key,T>
     */
    private readonly ReadableCollection $slicedCollection;

    /**
     * @param Selectable<array-key,T> $selectable
     */
    public function __construct(
        public readonly Selectable $selectable,
        private readonly float $currentPage,
        private readonly float $itemsPerPage,
    ) {
        $criteria = Criteria::create()
            ->setFirstResult((int) (($currentPage - 1) * $itemsPerPage))
            ->setMaxResults((int) $itemsPerPage);

        $this->slicedCollection = $selectable->matching($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->slicedCollection->count();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Traversable<T>
     */
    public function getIterator(): \Traversable
    {
        return $this->slicedCollection->getIterator();
    }
}
