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

namespace ApiPlatform\Core\Api;

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
     * Returns an array with the filter parameter names as keys and array with the following data as values:
     *   - property: the property where the filter is applied
     *   - type: the type of the filter
     *   - required: if this filter is required
     *   - strategy: the used strategy
     *   - swagger (optional): additional parameters for the path operation, e.g. 'swagger' => ['description' => 'My Description']
     * The description can contain additional data specific to a filter.
     *
     * @param string $resourceClass
     *
     * @return array
     */
    public function getDescription(string $resourceClass): array;
}
