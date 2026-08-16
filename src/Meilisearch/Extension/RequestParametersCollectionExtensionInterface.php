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

namespace ApiPlatform\Meilisearch\Extension;

use ApiPlatform\Metadata\Operation;

/**
 * Incrementally contributes to a Meilisearch search-parameters array
 * (q, filter, sort, facets, ...) while querying a resource collection.
 *
 * Unlike Elasticsearch's RequestBodySearchCollectionExtensionInterface, this
 * operates on Meilisearch's flat parameters array rather than a nested query
 * body -- there is no query-DSL tree to merge.
 */
interface RequestParametersCollectionExtensionInterface
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function applyToCollection(array $parameters, string $resourceClass, ?Operation $operation = null, array $context = []): array;
}
