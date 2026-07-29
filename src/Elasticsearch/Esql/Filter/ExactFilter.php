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
 * Matches documents whose field is exactly equal to one of the given values.
 *
 * Usage: `new QueryParameter(filter: new ExactFilter(), property: 'genre')`,
 * then `?genre=fantasy` or `?genre[]=fantasy&genre[]=sci-fi`.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class ExactFilter implements FilterInterface
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
        $whereClause = ($context['esql_where_clause'] ?? 'andWhere');

        if (\is_array($value)) {
            $placeholders = array_map(static fn (mixed $v): string => $query->param($v), array_values($value));
            $query->{$whereClause}(\sprintf('%s IN (%s)', $fieldPlaceholder, implode(', ', $placeholders)));

            return;
        }

        $query->{$whereClause}(\sprintf('%s == %s', $fieldPlaceholder, $query->param($value)));
    }
}
