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
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Illuminate\Support\Facades\Cache;

final class CachePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    /**
     * @var array<string, ApiProperty>
     */
    private array $localCache = [];

    public function __construct(
        private readonly PropertyMetadataFactoryInterface $decorated,
        private readonly string $cacheStore,
        private readonly ?ModelMetadata $modelMetadata = null,
    ) {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $key = hash('xxh3', serialize(['resource_class' => $resourceClass, 'property' => $property] + $options));

        if (isset($this->localCache[$key])) {
            return $this->localCache[$key];
        }

        $store = Cache::store($this->cacheStore);
        if (null !== $propertyMetadata = $store->get($key)) {
            return $this->localCache[$key] = $propertyMetadata;
        }

        $missingTableReads = $this->modelMetadata?->getMissingTableReads();
        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        // built from a missing table: the next call, maybe in another process, has to read it again
        if ($missingTableReads !== $this->modelMetadata?->getMissingTableReads()) {
            return $propertyMetadata;
        }

        $store->forever($key, $propertyMetadata);

        return $this->localCache[$key] = $propertyMetadata;
    }
}
