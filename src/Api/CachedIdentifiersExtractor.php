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

    public function __construct(CacheItemPoolInterface $cacheItemPool, IdentifiersExtractorInterface $decorated, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifiersFromItem($item): array
    {
        $identifiers = [];
        $resourceClass = $this->getObjectClass($item);

        $cacheKey = self::CACHE_KEY_PREFIX.md5($resourceClass);

        // This is to avoid setting the cache twice in the case where the related item cache doesn't exist
        $cacheIsHit = false;

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
            $isRelationCached = true;

            if ($cacheIsHit = $cacheItem->isHit()) {
                foreach ($cacheItem->get() as $propertyName) {
                    $identifiers[$propertyName] = $this->propertyAccessor->getValue($item, $propertyName);

                    if (!is_object($identifiers[$propertyName])) {
                        continue;
                    }

                    $relatedItem = $identifiers[$propertyName];
                    $relatedCacheKey = self::CACHE_KEY_PREFIX.md5($this->getObjectClass($relatedItem));

                    $relatedCacheItem = $this->cacheItemPool->getItem($relatedCacheKey);

                    if (!$relatedCacheItem->isHit()) {
                        $isRelationCached = false;
                        break;
                    }

                    unset($identifiers[$propertyName]);

                    $identifiers[$propertyName] = $this->propertyAccessor->getValue($relatedItem, $relatedCacheItem->get()[0]);
                }

                if (true === $isRelationCached) {
                    return $identifiers;
                }
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $identifiers = $this->decorated->getIdentifiersFromItem($item);

        if (isset($cacheItem) && false === $cacheIsHit) {
            try {
                $cacheItem->set(array_keys($identifiers));
                $this->cacheItemPool->save($cacheItem);
            } catch (CacheException $e) {
                // do nothing
            }
        }

        return $identifiers;
    }
}
