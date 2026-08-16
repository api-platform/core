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
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;

/**
 * Sorts the collection by a property.
 *
 * Usage: `new QueryParameter(filter: new SortFilter(), property: 'rating')`
 * with a key like `sort[:property]`, then `?sort[rating]=desc`.
 *
 * Note: this filter intentionally does not implement the
 * {@see \ApiPlatform\Metadata\SortFilterInterface} marker yet: it only drives
 * the GraphQL sort arguments, and GraphQL operations never use the ES|QL
 * providers. Implementing it would also require api-platform/metadata 5.0,
 * which the component test suites cannot guarantee yet.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class SortFilter implements FilterInterface, JsonSchemaFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function apply(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (null === $parameter) {
            return;
        }

        $value = $parameter->getValue(null);
        if (!\is_string($value) || !\in_array($direction = strtoupper($value), ['ASC', 'DESC'], true)) {
            return;
        }

        $field = $context['es_field'] ?? $parameter->getProperty() ?? $parameter->getKey();
        if (null === $field) {
            return;
        }

        $query->sort($field, $direction);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'string', 'enum' => ['asc', 'desc', 'ASC', 'DESC']];
    }
}
