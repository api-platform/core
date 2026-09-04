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

    /** @var array<string, ResourceMetadataCollection> */
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
        if (isset($this->localCache[$cacheKey])) {
            return $this->localCache[$cacheKey];
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        } catch (CacheException) {
            return $this->localCache[$cacheKey] = $this->decorated->create($resourceClass);
        }

        if ($cacheItem->isHit()) {
            return $this->localCache[$cacheKey] = new ResourceMetadataCollection($resourceClass, $cacheItem->get());
        }

        $resourceMetadataCollection = $this->decorated->create($resourceClass);
        $cacheItem->set((array) $resourceMetadataCollection);
        $this->cacheItemPool->save($cacheItem);

        return $this->localCache[$cacheKey] = $resourceMetadataCollection;
    }
}
