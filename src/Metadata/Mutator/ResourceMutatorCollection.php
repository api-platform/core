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

use Psr\Container\ContainerInterface;

final class ResourceMutatorCollection implements ContainerInterface
{
    private array $mutators;

    public function addMutator(string $resourceClass, object $mutator): void
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
