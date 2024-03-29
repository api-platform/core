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

namespace ApiPlatform\Metadata;

if (interface_exists(\ApiPlatform\Api\FilterInterface::class)) {
    class_alias(
        \ApiPlatform\Api\FilterInterface::class,
        __NAMESPACE__.'\FilterInterface'
    );

    if (false) { // @phpstan-ignore-line
        interface FilterInterface extends \ApiPlatform\Api\FilterInterface
        {
        }
    }
} else {
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
         *
         * @return array<string, array{property: string, type: string, required: bool, strategy: string, is_collection: bool, openapi: array<string, mixed>, schema: array<string, mixed>}>
         */
        public function getDescription(string $resourceClass): array;
    }
}
