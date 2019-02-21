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

namespace ApiPlatform\Core\Bridge\Doctrine\Common\Filter;

/**
 * Interface for ordering the collection by given properties.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Théo FIDRY <theo.fidry@gmail.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
interface OrderFilterInterface
{
    const DIRECTION_ASC = 'ASC';
    const DIRECTION_DESC = 'DESC';
    const NULLS_SMALLEST = 'nulls_smallest';
    const NULLS_LARGEST = 'nulls_largest';
    const NULLS_DIRECTION_MAP = [
        self::NULLS_SMALLEST => [
            'ASC' => 'ASC',
            'DESC' => 'DESC',
        ],
        self::NULLS_LARGEST => [
            'ASC' => 'DESC',
            'DESC' => 'ASC',
        ],
    ];
}
