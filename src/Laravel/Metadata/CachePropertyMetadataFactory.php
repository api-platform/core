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

final class CachePropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use MetadataCacheTrait;

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
        return $this->cached(
            hash('xxh3', serialize(['resource_class' => $resourceClass, 'property' => $property] + $options)),
            fn (): ApiProperty => $this->decorated->create($resourceClass, $property, $options),
        );
    }
}
