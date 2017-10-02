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

use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource metadata.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    const CACHE_KEY_PREFIX = 'resource_metadata_';

    private $cacheItemPool;
    private $decorated;
    private $localCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, ResourceMetadataFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadata
    {
        if (isset($this->localCache[$resourceClass])) {
            return $this->localCache[$resourceClass];
        }

        $cacheKey = self::CACHE_KEY_PREFIX.md5($resourceClass);

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $this->localCache[$resourceClass] = $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $resourceMetadata = $this->decorated->create($resourceClass);

        if (!isset($cacheItem)) {
            return $this->localCache[$resourceClass] = $resourceMetadata;
        }

        $cacheItem->set($resourceMetadata);
        $this->cacheItemPool->save($cacheItem);

        return $this->localCache[$resourceClass] = $resourceMetadata;
    }
}
