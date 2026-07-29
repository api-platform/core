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
use ApiPlatform\Metadata\FilterInterface as BaseFilterInterface;
use ApiPlatform\Metadata\Operation;

/**
 * Alters the ES|QL query according to the current parameter.
 *
 * Filters implementing this interface are designed for use with Parameters (QueryParameter):
 * the current parameter is available in `$context['parameter']` and the Elasticsearch field
 * name (already processed by the name converter) in `$context['es_field']`.
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function apply(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): void;
}
