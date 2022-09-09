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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;

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
     * @return T|PartialPaginatorInterface<T>|iterable<T>|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []);
}
