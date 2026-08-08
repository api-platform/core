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

use ApiPlatform\Metadata\FilterInterface as BaseFilterInterface;
use ApiPlatform\Metadata\Operation;

/**
 * A filter contributes a fragment to a Meilisearch search-parameters array.
 * Multiple filters on the same resource compose by AND-joining their
 * `filter` expression fragments (see FilterExtension) -- simpler than
 * Elasticsearch's bool/must JSON-tree merging, since Meilisearch's filter
 * syntax is a single flat expression string.
 *
 * Extends the core FilterInterface (still required by
 * ParameterValidationResourceMetadataCollectionFactory for `filters:`-
 * referenced services as of 5.0-alpha, despite getDescription() itself
 * being marked @deprecated there since 4.2) so filters declared this way
 * satisfy both contracts.
 */
interface FilterInterface extends BaseFilterInterface
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function apply(array $parameters, string $resourceClass, ?Operation $operation = null, array $context = []): array;
}
