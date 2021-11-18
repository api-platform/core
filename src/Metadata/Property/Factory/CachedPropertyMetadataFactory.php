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
use ApiPlatform\Util\CachedTrait;
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

    private $decorated;

    public function __construct(CacheItemPoolInterface $cacheItemPool, PropertyMetadataFactoryInterface $decorated)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5(serialize([$resourceClass, $property, $options]));

        return $this->getCached($cacheKey, function () use ($resourceClass, $property, $options) {
            return $this->decorated->create($resourceClass, $property, $options);
        });
    }
}
