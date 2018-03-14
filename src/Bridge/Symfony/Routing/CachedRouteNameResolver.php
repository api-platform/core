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
    public function getRouteName(string $resourceClass, $operationType /**, array $context = []**/): string
    {
        $context = \func_num_args() > 2 ? func_get_arg(2) : [];
        $cacheKey = self::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $operationType, $context['subresource_resources'] ?? null]));

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }
        } catch (CacheException $e) {
            // do nothing
        }

        if (\func_num_args() > 2) {
            $context = func_get_arg(2);
        } else {
            $context = [];
        }

        $routeName = $this->decorated->getRouteName($resourceClass, $operationType, $context);

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
