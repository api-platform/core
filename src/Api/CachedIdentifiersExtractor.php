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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Util\ClassInfoTrait;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * {@inheritdoc}
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class CachedIdentifiersExtractor implements IdentifiersExtractorInterface
{
    use ClassInfoTrait;

    const CACHE_KEY_PREFIX = 'iri_identifiers';

    private $cacheItemPool;
    private $propertyAccessor;
    private $decorated;
    private $localCache = [];
    private $localResourceCache = [];

    public function __construct(CacheItemPoolInterface $cacheItemPool, IdentifiersExtractorInterface $decorated, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromResourceClass(string $resourceClass): array
    {
        if (isset($this->localResourceCache[$resourceClass])) {
            return $this->localResourceCache[$resourceClass];
        }

        return $this->localResourceCache[$resourceClass] = $this->decorated->getIdentifiersFromResourceClass($resourceClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromItem($item): array
    {
        $keys = $this->getKeys($item, function ($item) use (&$identifiers) {
            return $identifiers = $this->decorated->getIdentifiersFromItem($item);
        });

        if (null !== $identifiers) {
            return $identifiers;
        }

        $identifiers = [];
        foreach ($keys as $propertyName) {
            $identifiers[$propertyName] = $this->propertyAccessor->getValue($item, $propertyName);

            if (!\is_object($identifiers[$propertyName])) {
                continue;
            }

            $relatedResourceClass = $this->getObjectClass($identifiers[$propertyName]);
            if (!$relatedIdentifiers = $this->localCache[$relatedResourceClass] ?? false) {
                $relatedCacheKey = self::CACHE_KEY_PREFIX.md5($relatedResourceClass);
                try {
                    $relatedCacheItem = $this->cacheItemPool->getItem($relatedCacheKey);
                    if (!$relatedCacheItem->isHit()) {
                        return $this->decorated->getIdentifiersFromItem($item);
                    }
                } catch (CacheException $e) {
                    return $this->decorated->getIdentifiersFromItem($item);
                }

                $relatedIdentifiers = $relatedCacheItem->get();
            }

            $identifiers[$propertyName] = $this->propertyAccessor->getValue($identifiers[$propertyName], $relatedIdentifiers[0]);
        }

        return $identifiers;
    }

    private function getKeys($item, callable $retriever): array
    {
        $resourceClass = $this->getObjectClass($item);
        if (isset($this->localCache[$resourceClass])) {
            return $this->localCache[$resourceClass];
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem(self::CACHE_KEY_PREFIX.md5($resourceClass));
            if ($cacheItem->isHit()) {
                return $this->localCache[$resourceClass] = $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $keys = array_keys($retriever($item));

        if (isset($cacheItem)) {
            $cacheItem->set($keys);
            $this->cacheItemPool->save($cacheItem);
        }

        return $this->localCache[$resourceClass] = $keys;
    }
}
