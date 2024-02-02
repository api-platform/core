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

namespace ApiPlatform\Elasticsearch\Extension;

use ApiPlatform\Metadata\Operation;

/**
 * Interface of Elasticsearch request body search extensions for collection queries.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html
 *
 * @experimental
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
interface RequestBodySearchCollectionExtensionInterface
{
    public function applyToCollection(array $requestBody, string $resourceClass, ?Operation $operation = null, array $context = []): array;
}
