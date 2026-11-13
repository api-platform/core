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

final class CachePropertyNameCollectionMetadataFactory implements PropertyNameCollectionFactoryInterface
{
    use MetadataCacheTrait;

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
        return $this->cached(
            hash('xxh3', serialize(['resource_class' => $resourceClass] + $options)),
            fn (): PropertyNameCollection => $this->decorated->create($resourceClass, $options),
        );
    }
}
