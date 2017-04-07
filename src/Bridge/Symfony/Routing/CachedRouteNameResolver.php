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

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * {@inheritdoc}
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedRouteNameResolver implements RouteNameResolverInterface
{
    const CACHE_KEY_PREFIX = 'route_name_';

    private $cacheItemPool;
    private $decorated;

    public function __construct(CacheItemPoolInterface $cacheItemPool, RouteNameResolverInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteName(string $resourceClass, bool $collection): string
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $collection]));

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        $routeName = $this->decorated->getRouteName($resourceClass, $collection);

        if (!isset($cacheItem)) {
            return $routeName;
        }

        try {
            $cacheItem->set($routeName);
            $this->cacheItemPool->save($cacheItem);
        } catch (CacheException $e) {
            // do nothing
        }

        return $routeName;
    }
}
