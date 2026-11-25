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

namespace ApiPlatform\HttpCache;

use ApiPlatform\Metadata\Operation;

/**
 * Collects extra HTTP cache tags to invalidate for a given resource.
 */
interface PurgeTagProviderInterface
{
    /**
     * @param object               $resource the persisted object (the entity or document when the operation declares stateOptions), or the object about to be removed on a delete operation
     * @param array<string, mixed> $context  $context['previous_data'] holds the API resource as read before the request, null on creation
     *
     * @return iterable<string>
     */
    public function getTags(object $resource, Operation $operation, array $context = []): iterable;
}
