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

namespace ApiPlatform\Symfony\Bundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheClearer\Psr6CacheClearer;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Clears the cache pools when warming up the cache.
 *
 * Do not use in production!
 *
 * @internal
 */
final class CachePoolClearerCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private readonly Psr6CacheClearer $poolClearer, private readonly array $pools = [])
    {
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->pools as $pool) {
            if ($this->poolClearer->hasPool($pool)) {
                $this->poolClearer->clearPool($pool);
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        // optional cache warmers are not run when handling the request
        return false;
    }
}
