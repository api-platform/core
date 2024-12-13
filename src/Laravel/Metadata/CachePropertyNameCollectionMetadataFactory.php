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

use ApiPlatform\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Property\PropertyNameCollection;
use Illuminate\Support\Facades\Cache;

final readonly class CachePropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    public function __construct(
        private PropertyNameCollectionFactoryInterface $decorated,
        private string $cacheStore,
    ) {
    }

    public function create(string $resourceClass, array $options = []): PropertyNameCollection
    {
        $key = hash('xxh3', serialize(['resource_class' => $resourceClass] + $options));

        return Cache::store($this->cacheStore)->rememberForever($key, function () use ($resourceClass, $options) {
            return $this->decorated->create($resourceClass, $options);
        });
    }
}
