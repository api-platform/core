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

namespace ApiPlatform\Core\Operation\Factory;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
final class CachedSubresourceOperationFactory implements SubresourceOperationFactoryInterface
{
    const CACHE_KEY_PREFIX = 'subresource_operations_';

    private $cacheItemPool;
    private $decorated;
    private $localCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, SubresourceOperationFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($resourceClass);

        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $this->localCache[$cacheKey] = $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $subresourceOperations = $this->decorated->create($resourceClass);

        if (!isset($cacheItem)) {
            return $this->localCache[$cacheKey] = $subresourceOperations;
        }

        try {
            $cacheItem->set($subresourceOperations);
            $this->cacheItemPool->save($cacheItem);
        } catch (CacheException $e) {
            // do nothing
        }

        return $this->localCache[$cacheKey] = $subresourceOperations;
    }
}
