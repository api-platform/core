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

namespace ApiPlatform\Elasticsearch\Esql\Extension;

use ApiPlatform\Elasticsearch\Esql\EsqlQuery;
use ApiPlatform\Metadata\Operation;

/**
 * Executes the ES|QL query and returns the collection result, short-circuiting
 * the provider (e.g. to return a paginator).
 *
 * @experimental
 *
 * @author Julien Lary <julien.lary@les-tilleuls.coop>
 */
interface ResultCollectionExtensionInterface extends CollectionExtensionInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function supportsResult(string $resourceClass, ?Operation $operation = null, array $context = []): bool;

    /**
     * @param array<string, mixed> $context
     *
     * @return iterable<object>
     */
    public function getResult(EsqlQuery $query, string $resourceClass, ?Operation $operation = null, array $context = []): iterable;
}
