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

use ApiPlatform\Laravel\Eloquent\Metadata\ModelMetadata;
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
        private readonly ?ModelMetadata $modelMetadata = null,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        if (isset($this->localCache[$resourceClass])) {
            return $this->localCache[$resourceClass];
        }

        $store = Cache::store($this->cacheStore);
        if (null !== $resourceMetadataCollection = $store->get($resourceClass)) {
            return $this->localCache[$resourceClass] = $resourceMetadataCollection;
        }

        $missingTableReads = $this->modelMetadata?->getMissingTableReads();
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        // built from a missing table: the next call, maybe in another process, has to read it again
        if ($missingTableReads !== $this->modelMetadata?->getMissingTableReads()) {
            return $resourceMetadataCollection;
        }

        $store->forever($resourceClass, $resourceMetadataCollection);

        return $this->localCache[$resourceClass] = $resourceMetadataCollection;
    }
}
