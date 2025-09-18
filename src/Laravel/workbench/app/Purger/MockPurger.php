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

namespace Workbench\App\Purger;

use ApiPlatform\HttpCache\PurgerInterface;

class MockPurger implements PurgerInterface
{
    /**
     * @var string[]
     */
    public static array $purgedTags = [];

    public function purge(array $tags): void
    {
        self::$purgedTags = array_merge(self::$purgedTags, $tags);
    }

    public static function reset(): void
    {
        self::$purgedTags = [];
    }

    /**
     * @return string[]
     */
    public static function getPurgedTags(): array
    {
        // Return unique and sorted tags for consistent assertions
        $tags = array_unique(self::$purgedTags);
        sort($tags);

        return $tags;
    }

    public function getResponseHeaders(array $iris): array
    {
        return [];
    }
}
