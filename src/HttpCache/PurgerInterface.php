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

/**
 * Purges resources from the cache.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface PurgerInterface
{
    /**
     * Purges all responses containing the given resources from the cache.
     *
     * @param string[] $iris
     */
    public function purge(array $iris): void;

    /**
     * Get the response header containing purged tags.
     *
     * @param string[] $iris
     */
    public function getResponseHeaders(array $iris): array;
}
