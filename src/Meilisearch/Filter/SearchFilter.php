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

namespace ApiPlatform\Meilisearch\Filter;

use ApiPlatform\Metadata\Operation;

/**
 * Free-text search filter: the first configured property found in the
 * request's filters becomes Meilisearch's `q` search term. Unlike
 * Elasticsearch's MatchFilter (which targets one field per query clause),
 * Meilisearch searches across every attribute declared in the index's
 * `searchableAttributes` at once -- `q` isn't property-scoped, so this
 * filter's job is just "is a free-text search active, and what's the term."
 *
 * Syntax: `?property=search+terms` (property name is only used to opt in
 * via `#[ApiFilter(SearchFilter::class, properties: ['title'])]`; the term
 * itself searches the whole index).
 */
final class SearchFilter extends AbstractFilter
{
    public function apply(array $parameters, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        if (isset($parameters['q']) && '' !== $parameters['q']) {
            return $parameters;
        }

        foreach ($context['filters'] ?? [] as $property => $value) {
            if (!$this->isPropertyEnabled($property) || !\is_string($value) || '' === $value) {
                continue;
            }

            $parameters['q'] = $value;

            return $parameters;
        }

        return $parameters;
    }
}
