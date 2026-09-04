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

namespace ApiPlatform\Laravel\Metadata;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Illuminate\Support\Facades\Cache;

final class CacheResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    /**
     * @var array<string, ResourceMetadataCollection>
     */
    private array $localCache = [];

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
        private readonly string $cacheStore,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        return $this->localCache[$resourceClass] ??= Cache::store($this->cacheStore)->rememberForever($resourceClass, function () use ($resourceClass) {
            return $this->decorated->create($resourceClass);
        });
    }
}
