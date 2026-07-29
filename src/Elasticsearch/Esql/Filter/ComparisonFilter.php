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
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;

/**
 * Compares a field against one or more bounds.
 *
 * Usage: `new QueryParameter(filter: new ComparisonFilter(), property: 'rating')`,
 * then `?rating[gt]=4`, `?rating[lte]=2`, `?rating[ne]=3` or `?rating[between]=2..4`.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class ComparisonFilter implements FilterInterface, JsonSchemaFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    private const OPERATORS = [
        'gt' => '>',
        'gte' => '>=',
        'lt' => '<',
        'lte' => '<=',
        'ne' => '!=',
    ];

    public const OPERATOR_BETWEEN = 'between';

    public function apply(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (null === $parameter) {
            return;
        }

        $values = $parameter->getValue(null);
        if (!\is_array($values)) {
            return;
        }

        $field = $context['es_field'] ?? $parameter->getProperty() ?? $parameter->getKey();
        if (null === $field) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property.', $parameter->getKey()));
        }

        $whereClause = ($context['esql_where_clause'] ?? 'andWhere');

        foreach ($values as $operator => $value) {
            if ('' === $value || null === $value) {
                continue;
            }

            if (self::OPERATOR_BETWEEN === $operator) {
                if (!\is_string($value) || 2 !== \count($bounds = explode('..', $value)) || '' === $bounds[0] || '' === $bounds[1]) {
                    throw new InvalidArgumentException(\sprintf('The "between" operator of the parameter "%s" expects a "min..max" value.', $parameter->getKey()));
                }

                $fieldPlaceholder = $query->identifier($field);
                $query->{$whereClause}(\sprintf('%1$s >= %2$s AND %1$s <= %3$s', $fieldPlaceholder, $query->param($bounds[0]), $query->param($bounds[1])));

                continue;
            }

            if (isset(self::OPERATORS[$operator])) {
                $query->{$whereClause}(\sprintf('%s %s %s', $query->identifier($field), self::OPERATORS[$operator], $query->param($value)));
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'gt' => ['type' => 'string'],
                'gte' => ['type' => 'string'],
                'lt' => ['type' => 'string'],
                'lte' => ['type' => 'string'],
                'ne' => ['type' => 'string'],
                'between' => ['type' => 'string', 'pattern' => '^.+\.\..+$'],
            ],
        ];
    }
}
