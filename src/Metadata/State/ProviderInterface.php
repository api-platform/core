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

namespace ApiPlatform\Metadata\State;

use ApiPlatform\Metadata\Operation;

/**
 * Retrieves data from a persistence layer.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @template T of object
 */
interface ProviderInterface
{
    /**
     * Provides data.
     *
     * @return T|Pagination\PartialPaginatorInterface<T>|iterable<T>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null;
}

class_alias(ProviderInterface::class, \ApiPlatform\State\ProviderInterface::class);
