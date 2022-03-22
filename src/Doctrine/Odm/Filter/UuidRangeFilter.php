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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\UuidRangeFilterTrait;
use ApiPlatform\Doctrine\Orm\Filter\AbstractRangeFilter;

/**
 * Filters the collection by range using UUIDs (UUID v6).
 *
 * @experimental
 *
 * @author Kai Dederichs <kai.dederichs@protonmail.com>
 */
final class UuidRangeFilter extends AbstractRangeFilter
{
    use UuidRangeFilterTrait;
}
