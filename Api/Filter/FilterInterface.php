<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api\Filter;

use Dunglas\ApiBundle\Api\ResourceInterface;

/**
 * Filters applicable on a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface FilterInterface
{
    /**
     * Gets the description of this filter for the given resource.
     *
     * Returns an array with the filter names as keys and array with the following data as values:
     *   - type: the type of the filter
     *   - strategy: the used strategy
     * The description can contain additional data specific to a filter.
     *
     * @param ResourceInterface $resource
     *
     * @return array
     */
    public function getDescription(ResourceInterface $resource);
}
