<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Api\Filter;

/**
 * Filters applicable on a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface FilterInterface
{
    const STRATEGY_EXACT = 'exact';

    /**
     * Gets name.
     *
     * @return string
     */
    public function getName();

    /**
     * Gets comparison strategy.
     *
     * @return string
     */
    public function getStrategy();
}
