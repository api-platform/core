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
 * Exact-match / facet filter: `?genres[]=Action&genres[]=Comedy` becomes the
 * Meilisearch filter expression `(genres = "Action" OR genres = "Comedy")`.
 *
 * Requires the property to be declared in the index's `filterableAttributes`
 * -- Meilisearch 400s on an undeclared attribute, there is no equivalent
 * restriction in Elasticsearch's mapping-based approach. Index settings are
 * the caller's responsibility, not this filter's.
 *
 * Syntax: `?property=value` or `?property[]=value` for multiple values.
 */
final class TermFilter extends AbstractFilter
{
    public function apply(array $parameters, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $expressions = [];

        foreach ($context['filters'] ?? [] as $property => $values) {
            if (!$this->isPropertyEnabled($property)) {
                continue;
            }

            $values = (array) $values;
            if (!$values) {
                continue;
            }

            $terms = array_map(fn ($value): string => \sprintf('%s = %s', $property, $this->quote($value)), $values);
            $expressions[] = \count($terms) > 1 ? '('.implode(' OR ', $terms).')' : $terms[0];
        }

        if (!$expressions) {
            return $parameters;
        }

        $parameters['filterExpressions'] = array_merge($parameters['filterExpressions'] ?? [], $expressions);

        return $parameters;
    }
}
