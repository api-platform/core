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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Metadata\Property\PropertyNameCollection;
use ApiPlatform\Metadata\Util\CachedTrait;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches property name collection.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedPropertyNameCollectionFactory implements PropertyNameCollectionFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY_PREFIX = 'property_name_collection_';

    public function __construct(CacheItemPoolInterface $cacheItemPool, private readonly PropertyNameCollectionFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $options]));

        return $this->getCached($cacheKey, fn (): PropertyNameCollection => $this->decorated->create($resourceClass, $options));
    }
}
