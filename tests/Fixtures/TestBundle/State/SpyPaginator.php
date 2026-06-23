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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A paginator whose scalar metadata is canned but whose rows are never meant to be
 * read: getIterator() and count() throw. A HEAD request must return without iterating,
 * proving no row SELECT was issued.
 *
 * @implements PaginatorInterface<object>
 * @implements \IteratorAggregate<object>
 */
final class SpyPaginator implements PaginatorInterface, \IteratorAggregate
{
    public function getCurrentPage(): float
    {
        return 1.;
    }

    public function getItemsPerPage(): float
    {
        return 30.;
    }

    public function getLastPage(): float
    {
        return 1.;
    }

    public function getTotalItems(): float
    {
        return 42.;
    }

    public function count(): int
    {
        throw new HttpException(Response::HTTP_I_AM_A_TEAPOT, 'iterated on HEAD');
    }

    public function getIterator(): \Iterator
    {
        throw new HttpException(Response::HTTP_I_AM_A_TEAPOT, 'iterated on HEAD');
    }
}
