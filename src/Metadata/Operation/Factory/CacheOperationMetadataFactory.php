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

namespace ApiPlatform\Metadata\Operation\Factory;

use ApiPlatform\Metadata\Operation;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches operation metadata.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class CacheOperationMetadataFactory implements OperationMetadataFactoryInterface
{
    public const CACHE_KEY_PREFIX = 'operation_metadata_';
    private array $localCache = [];

    public function __construct(private readonly CacheItemPoolInterface $cacheItemPool, private readonly OperationMetadataFactoryInterface $decorated)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $uriTemplate, array $context = []): ?Operation
    {
        $cacheKey = self::CACHE_KEY_PREFIX.hash('xxh3', $uriTemplate);
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return $this->localCache[$cacheKey];
        }

        try {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        } catch (CacheException) {
            $operation = $this->decorated->create($uriTemplate, $context);
            $this->localCache[$cacheKey] = $operation;

            return $operation;
        }

        if ($cacheItem->isHit()) {
            $this->localCache[$cacheKey] = $cacheItem->get();

            return $this->localCache[$cacheKey];
        }

        $operation = $this->decorated->create($uriTemplate, $context);
        $this->localCache[$cacheKey] = $operation;
        $cacheItem->set($this->localCache[$cacheKey]);
        $this->cacheItemPool->save($cacheItem);

        return $operation;
    }
}
