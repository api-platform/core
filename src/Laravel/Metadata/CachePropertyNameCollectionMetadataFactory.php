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
use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use Illuminate\Support\Facades\Cache;

final class CachePropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    /**
     * @var array<string, PropertyNameCollection>
     */
    private array $localCache = [];

    public function __construct(
        private readonly PropertyNameCollectionFactoryInterface $decorated,
        private readonly string $cacheStore,
        private readonly ?ModelMetadata $modelMetadata = null,
    ) {
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $key = hash('xxh3', serialize(['resource_class' => $resourceClass] + $options));

        if (isset($this->localCache[$key])) {
            return $this->localCache[$key];
        }

        $store = Cache::store($this->cacheStore);
        if (null !== $propertyNameCollection = $store->get($key)) {
            return $this->localCache[$key] = $propertyNameCollection;
        }

        $missingTableReads = $this->modelMetadata?->getMissingTableReads();
        $propertyNameCollection = $this->decorated->create($resourceClass, $options);

        // built from a missing table: the next call, maybe in another process, has to read it again
        if ($missingTableReads !== $this->modelMetadata?->getMissingTableReads()) {
            return $propertyNameCollection;
        }

        $store->forever($key, $propertyNameCollection);

        return $this->localCache[$key] = $propertyNameCollection;
    }
}
