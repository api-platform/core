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

namespace ApiPlatform\Api;

if (interface_exists(\ApiPlatform\Metadata\FilterInterface::class)) {
    trigger_deprecation('api-platform', '3.3', sprintf('%s is deprecated in favor of %s. This class will be removed in 4.0.', FilterInterface::class, \ApiPlatform\Metadata\FilterInterface::class));
    class_alias(
        \ApiPlatform\Metadata\FilterInterface::class,
        __NAMESPACE__.'\FilterInterface'
    );

    if (false) { // @phpstan-ignore-line
        interface FilterInterface extends \ApiPlatform\Metadata\FilterInterface
        {
        }
    }
} else {
    /**
     * Filters applicable on a resource.
     *
     * @author Kévin Dunglas <dunglas@gmail.com>
     *
     * @deprecated
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
         *   - strategy (optional): the used strategy
         *   - is_collection (optional): if this filter is for collection
         *   - swagger (optional): additional parameters for the path operation,
         *     e.g. 'swagger' => [
         *       'description' => 'My Description',
         *       'name' => 'My Name',
         *       'type' => 'integer',
         *     ]
         *   - openapi (optional): additional parameters for the path operation in the version 3 spec,
         *     e.g. 'openapi' => [
         *       'description' => 'My Description',
         *       'name' => 'My Name',
         *       'schema' => [
         *          'type' => 'integer',
         *       ]
         *     ]
         *   - schema (optional): schema definition,
         *     e.g. 'schema' => [
         *       'type' => 'string',
         *       'enum' => ['value_1', 'value_2'],
         *     ]
         * The description can contain additional data specific to a filter.
         *
         * @see \ApiPlatform\OpenApi\Factory\OpenApiFactory::getFiltersParameters
         */
        public function getDescription(string $resourceClass): array;
    }
}
