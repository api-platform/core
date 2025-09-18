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
use Psr\Container\ContainerInterface;

/**
 * Collection of Resource mutators to mutate ApiResource metadata.
 */
interface ResourceMutatorCollectionInterface extends ContainerInterface
{
    /**
     * @return list<ResourceMutatorInterface>
     */
    public function get(string $id): array;

    public function add(string $resourceClass, ResourceMutatorInterface $mutator): void;
}
