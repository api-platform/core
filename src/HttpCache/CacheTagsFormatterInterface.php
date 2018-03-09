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

namespace ApiPlatform\Core\HttpCache;

/**
 * Allows cache purgers to format the cache tags header according
 * to their own needs.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
interface CacheTagsFormatterInterface
{
    /**
     * Formats the IRIs so they are compatible with
     * the purger implementation.
     *
     * @param string[] $iris
     */
    public function formatTags(array $iris): string;
}
