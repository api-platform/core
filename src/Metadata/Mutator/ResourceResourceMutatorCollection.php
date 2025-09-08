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

use ApiPlatform\Metadata\ResourceMutatorInterface;

/**
 * @internal
 */
final class ResourceResourceMutatorCollection implements ResourceMutatorCollectionInterface
{
    private array $mutators;

    public function add(string $resourceClass, ResourceMutatorInterface $mutator): void
    {
        $this->mutators[$resourceClass][] = $mutator;
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
