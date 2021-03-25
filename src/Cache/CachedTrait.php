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

namespace ApiPlatform\Core\Cache;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CallbackInterface;

/**
 * @internal
 */
trait CachedTrait
{
    /** @var CacheItemPoolInterface */
    private $cacheItemPool;
    private $localCache = [];

    /**
     * @param callable|CallbackInterface $getValue
     */
    private function getCached(string $cacheKey, $getValue)
    {
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return $this->localCache[$cacheKey];
        }

        $save = true;
        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        } catch (CacheException $e) {
            return $this->localCache[$cacheKey] = $getValue(new CacheItem(), $save);
        }

        if ($cacheItem->isHit()) {
            return $this->localCache[$cacheKey] = $cacheItem->get();
        }

        $value = $getValue($cacheItem, $save);

        $cacheItem->set($value);
        // @phpstan-ignore-next-line
        if ($save) {
            $this->cacheItemPool->save($cacheItem);
            $this->localCache[$cacheKey] = $value;
        }

        return $value;
    }
}
