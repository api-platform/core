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

namespace ApiPlatform\Metadata\Mutator;

use ApiPlatform\Metadata\OperationMutatorInterface;

/**
 * @internal
 */
final class OperationResourceMutatorCollection implements OperationMutatorCollectionInterface
{
    private array $mutators = [];

    /**
     * Adds a mutator to the container for a given operation name.
     */
    public function add(string $operationName, OperationMutatorInterface $mutator): void
    {
        $this->mutators[$operationName][] = $mutator;
    }

    public function get(string $id): array
    {
        return $this->mutators[$id] ?? [];
    }

    public function has(string $id): bool
    {
        return isset($this->mutators[$id]);
    }
}
