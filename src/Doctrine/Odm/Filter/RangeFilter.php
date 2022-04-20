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

use ApiPlatform\Doctrine\Common\Filter\RangeFilterTrait;

/**
 * Filters the collection by range using numbers.
 *
 * @experimental
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 * @author Kai Dederichs <kai.dederichs@protonmail.com>
 */
final class RangeFilter extends AbstractRangeFilter
{
    use RangeFilterTrait;
}

class_alias(RangeFilter::class, \ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\RangeFilter::class);
