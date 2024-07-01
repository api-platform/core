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

namespace ApiPlatform\State\Pagination;

/**
 * @author Priyadi Iman Nurcahyo <priyadi@rekalogika.com>
 *
 * @template T of object
 *
 * @extends \Traversable<T>
 */
interface CursorPaginatorInterface extends \Countable, \Traversable
{
    public function getCurrentPageCursor(): ?string;

    public function getNextPageCursor(): ?string;

    public function getPreviousPageCursor(): ?string;

    public function getFirstPageCursor(): ?string;

    public function getLastPageCursor(): ?string;
}
