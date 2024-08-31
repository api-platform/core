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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use Illuminate\Support\Facades\Cache;

final readonly class CachePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(
        private PropertyMetadataFactoryInterface $decorated,
        private string $cacheStore,
    ) {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $key = hash('xxh3', serialize(['resource_class' => $resourceClass, 'property' => $property] + $options));

        return Cache::store($this->cacheStore)->rememberForever($key, function () use ($resourceClass, $property, $options) {
            return $this->decorated->create($resourceClass, $property, $options);
        });
    }
}
