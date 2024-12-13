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
     *   - description : the description of the filter
     *   - strategy: the used strategy
     *   - is_collection: if this filter is for collection
     *   - openapi: additional parameters for the path operation in the version 3 spec,
     *     e.g. 'openapi' => ApiPlatform\OpenApi\Model\Parameter(
     *       description: 'My Description',
     *       name: 'My Name',
     *       schema: [
     *          'type' => 'integer',
     *       ]
     *     )
     *   - schema: schema definition,
     *     e.g. 'schema' => [
     *       'type' => 'string',
     *       'enum' => ['value_1', 'value_2'],
     *     ]
     * The description can contain additional data specific to a filter.
     *
     * @see \ApiPlatform\OpenApi\Factory\OpenApiFactory::getFiltersParameters
     *
     * @param class-string $resourceClass
     *
     * @return array<string, array{property?: string, type?: string, required?: bool, description?: string, strategy?: string, is_collection?: bool, openapi?: \ApiPlatform\OpenApi\Model\Parameter, schema?: array<string, mixed>}>
     */
    public function getDescription(string $resourceClass): array;
}
