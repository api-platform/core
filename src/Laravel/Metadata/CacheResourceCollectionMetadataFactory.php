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

final class CacheResourceCollectionMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    use MetadataCacheTrait;

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
        return $this->cached($resourceClass, fn (): ResourceMetadataCollection => $this->decorated->create($resourceClass));
    }
}
