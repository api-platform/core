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

namespace ApiPlatform\Doctrine\Orm\Util;

/**
 * @internal
 */
final class BCHelper
{
    private function __construct()
    {
    }

    /**
     * Maps a string sort direction to the \SortDirection enum expected by doctrine/orm >= 3.7,
     * keeping the plain string for older versions and preserving existing enum values.
     *
     * doctrine/orm 3.7 deprecates passing strings (or null) as sort directions to
     * QueryBuilder::orderBy()/addOrderBy() in favor of the PHP 8.6 \SortDirection enum
     * (provided on older PHP versions through symfony/polyfill-php86). Since API Platform
     * still supports doctrine/orm ^2.17 || ^3.3, the enum cannot be used unconditionally.
     *
     * @see https://github.com/doctrine/orm/blob/3.7.x/UPGRADE.md
     */
    public static function sortDirection(string|\SortDirection $direction): string|object
    {
        if ($direction instanceof \SortDirection) {
            return $direction;
        }

        if (!interface_exists('Doctrine\ORM\Tools\Pagination\PaginatorInterface')) {
            // doctrine/orm < 3.7
            return $direction;
        }

        if (!enum_exists('SortDirection')) {
            // PHP < 8.6 (Safety check because doctrine/orm already provides a polyfill)
            return $direction;
        }

        return match (strtoupper($direction)) {
            'ASC' => \SortDirection::Ascending,
            'DESC' => \SortDirection::Descending,
            default => $direction,
        };
    }
}
