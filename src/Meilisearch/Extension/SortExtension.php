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

namespace ApiPlatform\Meilisearch\Extension;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;

/**
 * Applies the operation's default order (or a resource-wide default
 * direction) as Meilisearch `sort` entries (`["field:asc", ...]`).
 *
 * Sorting on a property requires it to be declared in the index's
 * `sortableAttributes` -- Meilisearch will 400 otherwise. That's the
 * caller's responsibility (see Options/index settings), not this class's.
 */
final class SortExtension implements RequestParametersCollectionExtensionInterface
{
    public function __construct(private readonly ?string $defaultDirection = null)
    {
    }

    public function applyToCollection(array $parameters, string $resourceClass, ?Operation $operation = null, array $context = []): array
    {
        $sort = [];

        if ($operation && null !== ($defaultOrder = $operation->getOrder())) {
            foreach ($defaultOrder as $property => $direction) {
                if (\is_int($property)) {
                    $property = $direction;
                    $direction = 'asc';
                }

                $sort[] = \sprintf('%s:%s', $property, strtolower((string) $direction));
            }
        } elseif (null !== $this->defaultDirection) {
            $property = 'id';
            if ($operation instanceof HttpOperation) {
                $uriVariables = $operation->getUriVariables()[0] ?? null;
                $property = $uriVariables ? $uriVariables->getIdentifiers()[0] ?? 'id' : 'id';
            }

            $sort[] = \sprintf('%s:%s', $property, strtolower($this->defaultDirection));
        }

        if (!$sort) {
            return $parameters;
        }

        $parameters['sort'] = array_merge($parameters['sort'] ?? [], $sort);

        return $parameters;
    }
}
