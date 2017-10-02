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

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (CacheException $e) {
            return $this->decorated->create($resourceClass);
        }

        $subresourceOperations = $this->decorated->create($resourceClass);

        $cacheItem->set($subresourceOperations);
        $this->cacheItemPool->save($cacheItem);

        return $subresourceOperations;
    }
}
