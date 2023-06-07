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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Util\CachedTrait;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Caches property metadata.
 *
 * @author Teoh Han Hui <teohhanhui@gmail.com>
 */
final class CachedPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    use CachedTrait;

    public const CACHE_KEY_PREFIX = 'property_metadata_';

    public function __construct(CacheItemPoolInterface $cacheItemPool, private readonly PropertyMetadataFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $property, $options]));

        return $this->getCached($cacheKey, fn (): ApiProperty => $this->decorated->create($resourceClass, $property, $options));
    }
}
