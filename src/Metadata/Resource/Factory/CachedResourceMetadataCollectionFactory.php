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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource metadata.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class CachedResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public const CACHE_KEY_PREFIX = 'resource_metadata_collection_';
    private array $localCache = [];

    public function __construct(private readonly CacheItemPoolInterface $cacheItemPool, private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $cacheKey = self::CACHE_KEY_PREFIX.hash('xxh3', $resourceClass);
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return new ResourceMetadataCollection($resourceClass, $this->localCache[$cacheKey]);
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        } catch (CacheException) {
            $resourceMetadataCollection = $this->decorated->create($resourceClass);
            $this->localCache[$cacheKey] = (array) $resourceMetadataCollection;

            return $resourceMetadataCollection;
        }

        if ($cacheItem->isHit()) {
            $this->localCache[$cacheKey] = $cacheItem->get();

            return new ResourceMetadataCollection($resourceClass, $this->localCache[$cacheKey]);
        }

        $resourceMetadataCollection = $this->decorated->create($resourceClass);
        $this->localCache[$cacheKey] = (array) $resourceMetadataCollection;
        $cacheItem->set($this->localCache[$cacheKey]);
        $this->cacheItemPool->save($cacheItem);

        return $resourceMetadataCollection;
    }
}
