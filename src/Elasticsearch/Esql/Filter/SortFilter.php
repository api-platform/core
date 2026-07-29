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
use ApiPlatform\Metadata\SortFilterInterface;

/**
 * Sorts the collection by a property.
 *
 * Usage: `new QueryParameter(filter: new SortFilter(), property: 'rating')`
 * with a key like `sort[:property]`, then `?sort[rating]=desc`.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
final class SortFilter implements FilterInterface, JsonSchemaFilterInterface, SortFilterInterface
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
