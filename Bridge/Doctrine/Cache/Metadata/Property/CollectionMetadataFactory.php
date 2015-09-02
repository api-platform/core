<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Bridge\Doctrine\Cache\Metadata\Property;

use Doctrine\Common\Cache\Cache;
use Dunglas\ApiBundle\Metadata\Property\Factory\CollectionMetadataFactoryInterface;

/**
 * Cache decorator.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class CollectionMetadataFactory implements CollectionMetadataFactoryInterface
{
    const KEY_PATTERN = 'pc_%s_%s';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var CollectionMetadataFactoryInterface
     */
    private $decorated;

    public function __construct(Cache $cache, CollectionMetadataFactoryInterface $decorated)
    {
        $this->cache = $cache;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $resourceClass, array $options = []) : CollectionMetadata
    {
        $key = sprintf(self::KEY_PATTERN, $resourceClass, serialize($options));

        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        $collectionMetadata = $this->decorated->create($resourceClass, $options);
        $this->cache->save($key, $collectionMetadata);

        return $collectionMetadata;
    }
}
