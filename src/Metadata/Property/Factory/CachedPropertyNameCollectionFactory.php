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

namespace ApiPlatform\Core\Metadata\Property\Factory;

use ApiPlatform\Core\Metadata\Property\PropertyNameCollection;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches property name collection.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    const CACHE_KEY_PREFIX = 'property_name_collection_';

    private $cacheItemPool;
    private $decorated;
    private $memoryCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, PropertyNameCollectionFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $localKey = serialize([$resourceClass, $options]);
        if (isset($this->memoryCache[$localKey])) {
            return $this->memoryCache[$localKey];
        }

        $cacheKey = self::CACHE_KEY_PREFIX.md5($localKey);

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $this->memoryCache[$localKey] = $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $propertyNameCollection = $this->decorated->create($resourceClass, $options);

        if (!isset($cacheItem)) {
            return $this->memoryCache[$localKey] = $propertyNameCollection;
        }

        $cacheItem->set($propertyNameCollection);
        $this->cacheItemPool->save($cacheItem);

        return $this->memoryCache[$localKey] = $propertyNameCollection;
    }
}
