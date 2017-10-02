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

namespace ApiPlatform\Core\Metadata\Resource\Factory;

use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource name collection.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    const CACHE_KEY = 'resource_name_collection';

    private $cacheItemPool;
    private $decorated;
    private $localCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, ResourceNameCollectionFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        if (isset($this->localCache[self::CACHE_KEY])) {
            return $this->localCache[self::CACHE_KEY];
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem(self::CACHE_KEY);

            if ($cacheItem->isHit()) {
                return $this->localCache[self::CACHE_KEY] = $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $resourceNameCollection = $this->decorated->create();

        if (!isset($cacheItem)) {
            return $this->localCache[self::CACHE_KEY] = $resourceNameCollection;
        }

        $cacheItem->set($resourceNameCollection);
        $this->cacheItemPool->save($cacheItem);

        return $this->localCache[self::CACHE_KEY] = $resourceNameCollection;
    }
}
