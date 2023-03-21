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

use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\Util\CachedTrait;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches resource name collection.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY = 'resource_name_collection';

    public function __construct(CacheItemPoolInterface $cacheItemPool, private readonly ResourceNameCollectionFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        return $this->getCached(self::CACHE_KEY, fn (): ResourceNameCollection => $this->decorated->create());
    }
}
