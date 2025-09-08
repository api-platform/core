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
use Psr\Container\ContainerInterface;

/**
 * Collection of Operation mutators to mutate Operation metadata.
 */
interface OperationMutatorCollectionInterface extends ContainerInterface
{
    /**
     * @return list<OperationMutatorInterface>
     */
    public function get(string $id): mixed;

    public function add(string $operationName, OperationMutatorInterface $mutator): void;
}
