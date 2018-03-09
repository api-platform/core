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
 * Formats the cache header as a comma-separated list.
 *
 * @author Yanick Witschi <yanick.witschi@terminal42.ch>
 */
class CsvFormatter implements CacheTagsFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function formatTags(array $iris): string
    {
        return implode(',', $iris);
    }
}
