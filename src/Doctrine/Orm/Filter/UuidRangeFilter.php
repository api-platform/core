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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\UuidRangeFilterTrait;

/**
 * Filters the collection by range using UUIDs.
 *
 * @author Kai Dederichs <kai.dederichs@protonmail.com>
 */
final class UuidRangeFilter extends AbstractRangeFilter
{
    use UuidRangeFilterTrait;
}
