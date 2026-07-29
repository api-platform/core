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

namespace ApiPlatform\Elasticsearch\Esql\Filter;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Operation;

/**
 * Matches documents using the MATCH full-text function.
 *
 * Usage: `new QueryParameter(filter: new MatchFilter(), property: 'title')`,
 * then `?title=hobbit`. Multiple values are combined with a logical OR.
 *
 * @see https://www.elastic.co/docs/reference/query-languages/esql/functions-operators/search-functions
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class MatchFilter implements FilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function apply(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (null === $parameter) {
            return;
        }

        $value = $parameter->getValue(null);
        if (null === $value || '' === $value || [] === $value) {
            return;
        }

        $field = $context['es_field'] ?? $parameter->getProperty() ?? $parameter->getKey();
        if (null === $field) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property.', $parameter->getKey()));
        }

        $fieldPlaceholder = $query->identifier($field);
        $conditions = array_map(
            static fn (mixed $v): string => \sprintf('MATCH(%s, %s)', $fieldPlaceholder, $query->param($v)),
            \is_array($value) ? array_values($value) : [$value]
        );

        $query->fullTextWhere(implode(' OR ', $conditions));
    }
}
