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

use ApiPlatform\Doctrine\Common\Filter\RangeFilterTrait;

/**
 * Filters the collection by range using numbers.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Kai Dederichs <kai.dederichs@protonmail.com>
 *
 * @final
 */
class RangeFilter extends AbstractRangeFilter
{
    use RangeFilterTrait;
}

class_alias(RangeFilter::class, \ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter::class);
